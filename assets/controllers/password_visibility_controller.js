import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];

    toggle(event) {
        const button = event.currentTarget;
        const field = button.closest('.password-field');
        const input = field?.querySelector('input');

        if (!input) {
            return;
        }

        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';

        button.classList.toggle('is-visible', !isVisible);
        button.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        button.setAttribute('title', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    }
}
