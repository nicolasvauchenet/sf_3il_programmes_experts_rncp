import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["promotion", "year", "submit"];
    static values = {
        yearsByPromotion: Object,
        autoSelectLatestYear: Boolean,
    };

    connect() {
        this.filterYears();
        this.check();
    }

    filterYears(selectLatestYear = false) {
        const promotion = this.promotionTarget.value;
        const selectedYear = this.yearTarget.value;
        const years = this.yearsByPromotionValue[promotion] ?? [];

        if (!promotion) {
            this.yearTarget.innerHTML = "";

            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = "Choisissez d'abord une promotion";
            this.yearTarget.appendChild(placeholder);

            this.yearTarget.disabled = true;
            this.yearTarget.value = "";

            return;
        }

        this.yearTarget.disabled = false;

        this.yearTarget.innerHTML = "";

        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "Choisissez un Millésime";
        this.yearTarget.appendChild(placeholder);

        years.forEach((year) => {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year.replace("-", " - ");

            if (year === selectedYear) {
                option.selected = true;
            }

            this.yearTarget.appendChild(option);
        });

        if (!selectLatestYear && years.includes(selectedYear)) {
            return;
        }

        this.yearTarget.value = (this.autoSelectLatestYearValue || selectLatestYear) && years.length > 0 ? years[0] : "";
    }

    onPromotionChange() {
        this.filterYears(true);
        this.check();
    }

    check() {
        const promotion = this.promotionTarget.value;
        const year = this.yearTarget.value;
        const isValid = promotion !== "" && year !== "";

        this.submitTarget.disabled = !isValid;
    }
}
