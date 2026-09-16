import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'item', 'empty', 'count', 'label'];

    static values = {
        block: String,
        singularLabel: String,
        pluralLabel: String,
    };

    connect() {
        this.blockFilterEnabled = this.hasBlockValue && this.normalize(this.blockValue) !== '';

        if (this.blockFilterEnabled) {
            const normalizedBlock = this.normalize(this.blockValue);

            this.inputTargets.forEach((input) => {
                input.value = normalizedBlock;
            });

            this.applyFilter('');
            return;
        }

        const initialQuery = this.hasInputTarget
            ? this.normalize(this.inputTargets[0].value ?? '')
            : '';

        this.applyFilter(initialQuery);
    }

    filter(event) {
        const rawQuery = event.target.value ?? '';
        const query = this.normalize(rawQuery);

        this.blockFilterEnabled = false;
        this.removeBlockParamFromCurrentUrl();
        this.removeBlockParamFromItemLinks();

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
        const expectedBlock = this.blockFilterEnabled && this.hasBlockValue
            ? this.normalize(this.blockValue)
            : '';

        this.itemTargets.forEach((item) => {
            const haystack = this.normalize(item.dataset.search ?? '');
            const itemBlock = this.normalize(item.dataset.block ?? '');

            const matchesQuery = query === '' || haystack.includes(query);
            const matchesBlock = expectedBlock === '' || itemBlock === expectedBlock;
            const matches = matchesQuery && matchesBlock;

            item.toggleAttribute('hidden', !matches);
        });

        const visibleCount = this.visibleDesktopItemsCount();

        this.emptyTargets.forEach((empty) => {
            empty.toggleAttribute('hidden', visibleCount > 0);
        });

        this.updateCounts(visibleCount);
    }

    visibleDesktopItemsCount() {
        return this.itemTargets.filter((item) => {
            const isDesktopItem = item.closest('.app-toc-desktop') !== null;

            return isDesktopItem && !item.hasAttribute('hidden');
        }).length;
    }

    updateCounts(visibleCount) {
        this.countTargets.forEach((count) => {
            count.textContent = String(visibleCount);
        });

        const singularLabel = this.singularLabelValue || 'élément';
        const pluralLabel = this.pluralLabelValue || `${singularLabel}s`;
        const currentLabel = visibleCount > 1 ? pluralLabel : singularLabel;

        this.labelTargets.forEach((label) => {
            label.textContent = currentLabel;
        });
    }

    removeBlockParamFromCurrentUrl() {
        const url = new URL(window.location.href);

        if (url.searchParams.has('block')) {
            url.searchParams.delete('block');
            window.history.replaceState({}, '', url);
        }
    }

    removeBlockParamFromItemLinks() {
        this.itemTargets.forEach((item) => {
            const href = item.getAttribute('href');

            if (!href) {
                return;
            }

            const url = new URL(href, window.location.origin);
            url.searchParams.delete('block');

            item.setAttribute('href', `${url.pathname}${url.search}${url.hash}`);
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
