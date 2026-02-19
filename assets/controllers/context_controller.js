import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["promotion", "year", "submit"];

    connect() {
        this.check();
    }

    check() {
        const promotion = this.promotionTarget.value;
        const year = this.yearTarget.value;
        const isValid = promotion !== "" && year !== "";

        this.submitTarget.disabled = !isValid;
    }
}
