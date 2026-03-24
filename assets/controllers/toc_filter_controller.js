import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'item', 'empty'];

    static values = {
        block: String,
    };

    connect() {
        const hasBlock = this.hasBlockValue && this.blockValue !== '';

        let initialQuery = '';

        if (hasBlock) {
            initialQuery = this.blockValue;

            this.inputTargets.forEach((input) => {
                input.value = this.blockValue;
            });
        } else if (this.hasInputTarget) {
            initialQuery = this.normalize(this.inputTargets[0].value ?? '');
        }

        this.applyFilter(this.normalize(initialQuery));
    }

    filter(event) {
        const rawQuery = event.target.value ?? '';
        const query = this.normalize(rawQuery);

        this.syncInputs(rawQuery, event.target);
        this.applyFilter(query);
    }

    syncInputs(rawValue, sourceInput) {
        this.inputTargets.forEach((input) => {
            if (input !== sourceInput) {
                input.value = rawValue;
            }
        });
    }

    applyFilter(query) {
        let visibleCount = 0;

        const expectedBlock = this.hasBlockValue
            ? this.normalize(this.blockValue)
            : '';

        this.itemTargets.forEach((item) => {
            const haystack = this.normalize(item.dataset.search ?? '');
            const itemBlock = this.normalize(item.dataset.block ?? '');

            const matchesQuery = query === '' || haystack.includes(query);
            const matchesBlock = expectedBlock === '' || itemBlock === expectedBlock;
            const matches = matchesQuery && matchesBlock;

            item.toggleAttribute('hidden', !matches);

            if (matches) {
                visibleCount++;
            }
        });

        this.emptyTargets.forEach((empty) => {
            empty.toggleAttribute('hidden', visibleCount > 0);
        });
    }

    normalize(value) {
        return String(value)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }
}
