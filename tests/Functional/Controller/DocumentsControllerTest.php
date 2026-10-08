<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\{Framework, Promotion, PromotionDocument, User};
use App\Enum\Program;
use App\Service\Admin\PromotionDocumentStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

final class DocumentsControllerTest extends WebTestCase
{
    public function testDocumentLifecycleAndPromotionIsolation(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $directory = sys_get_temp_dir().'/promotion-documents-'.bin2hex(random_bytes(6));
        (new Filesystem())->mkdir($directory);
        $storage = new PromotionDocumentStorage($directory);
        self::getContainer()->set(PromotionDocumentStorage::class, $storage);
        $source = $directory.'/test.pdf';
        file_put_contents($source, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $suffix = bin2hex(random_bytes(5));
        $framework = (new Framework())->setCode('RNCP-TEST-'.$suffix)->setTitle('Documents test')->setLevel(7)
            ->setStartAt(new \DateTimeImmutable('2030-09-01'))->setEndAt(new \DateTimeImmutable('2031-08-31'));
        $promotion = (new Promotion())->setProgram(Program::EADL)->setLabel('Documents '.$suffix)->setFramework($framework)
            ->setStartAt(new \DateTimeImmutable('2030-09-01'))->setEndAt(new \DateTimeImmutable('2031-08-31'));
        $other = (new Promotion())->setProgram(Program::EADL)->setLabel('Other '.$suffix)->setFramework($framework)
            ->setStartAt(new \DateTimeImmutable('2031-09-01'))->setEndAt(new \DateTimeImmutable('2032-08-31'));
        $admin = (new User())->setEmail($suffix.'@example.test')->setFullName('Documents Admin')->setPassword('unused')->setRoles(['ROLE_ADMIN']);
        $reader = (new User())->setEmail('reader-'.$suffix.'@example.test')->setFullName('Documents Reader')->setPassword('unused')->setRoles(['ROLE_USER']);
        foreach ([$framework, $promotion, $other, $admin, $reader] as $entity) $em->persist($entity);
        $em->flush();
        $base = '/administration/referentiels/'.$promotion->getId().'/documents';
        try {
            $client->loginUser($admin);
            $crawler = $client->request('GET', $base.'/nouveau');
            self::assertResponseIsSuccessful();
            $form = $crawler->selectButton('Enregistrer')->form();
            $form['promotion_document[label]'] = 'Mon référentiel';
            $form['promotion_document[position]'] = '2';
            $form['promotion_document[file]']->upload($source);
            $client->submit($form);
            self::assertResponseRedirects($base);
            $document = $em->getRepository(PromotionDocument::class)->findOneBy(['promotion' => $promotion]);
            self::assertNotNull($document);
            self::assertFileExists($storage->path($document->filename));
            $oldFile = $document->filename;
            $id = $document->id;

            $client->request('GET', '/administration/referentiels/'.$other->getId().'/documents/'.$id.'/modifier');
            self::assertResponseStatusCodeSame(404);
            $client->request('POST', $base.'/'.$id.'/supprimer', ['_token' => 'wrong']);
            self::assertResponseRedirects('/');
            self::assertFileExists($storage->path($oldFile));

            $crawler = $client->request('GET', $base.'/'.$id.'/modifier');
            $form = $crawler->selectButton('Enregistrer')->form();
            $form['promotion_document[label]'] = 'Document remplacé';
            $form['promotion_document[visible]']->untick();
            $form['promotion_document[file]']->upload($source);
            $client->submit($form);
            self::assertResponseRedirects($base);
            $document = $em->find(PromotionDocument::class, $id);
            self::assertNotSame($oldFile, $document->filename);
            self::assertFileDoesNotExist($storage->path($oldFile));
            self::assertFalse($document->visible);
            $client->request('GET', '/documents/'.$id);
            self::assertResponseIsSuccessful();

            $client->loginUser($reader);
            $client->request('GET', '/documents/'.$id);
            self::assertResponseStatusCodeSame(404);
            $client->request('GET', $base);
            self::assertResponseRedirects('/');
            $document = $em->find(PromotionDocument::class, $id);
            $document->visible = true;
            $em->flush();
            $client->request('GET', '/documents/'.$id);
            self::assertResponseIsSuccessful();
            self::assertTrue($client->getResponse()->headers->hasCacheControlDirective('private'));
            self::assertTrue($client->getResponse()->headers->hasCacheControlDirective('no-store'), (string) $client->getResponse()->headers->get('cache-control'));

            $client->loginUser($admin);
            $crawler = $client->request('GET', $base);
            $client->submit($crawler->selectButton('Supprimer')->form());
            self::assertResponseRedirects($base);
            self::assertNull($em->find(PromotionDocument::class, $id));
            self::assertFileDoesNotExist($storage->path($document->filename));
        } finally {
            foreach ($em->getRepository(PromotionDocument::class)->findBy(['promotion' => $promotion]) as $remaining) $em->remove($remaining);
            $em->flush();
            foreach ([$promotion, $other, $framework, $admin, $reader] as $entity) {
                $managed = $em->find($entity::class, $entity->getId());
                if ($managed) $em->remove($managed);
            }
            $em->flush();
            (new Filesystem())->remove($directory);
        }
    }
}
