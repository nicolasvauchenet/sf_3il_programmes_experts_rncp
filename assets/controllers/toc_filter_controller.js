import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'item', 'empty'];

    connect() {
        this.applyFilter('');
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

        this.itemTargets.forEach((item) => {
            const haystack = this.normalize(item.dataset.search ?? '');
            const matches = query === '' || haystack.includes(query);

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
