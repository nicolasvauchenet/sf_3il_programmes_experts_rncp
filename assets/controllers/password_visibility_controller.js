import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'button'];

    connect() {
        this.visible = false;
        this.refresh();
    }

    toggle() {
        this.visible = !this.visible;
        this.refresh();
    }

    refresh() {
        const type = this.visible ? 'text' : 'password';

        this.inputTargets.forEach((input) => {
            input.type = type;
        });

        if (this.hasButtonTarget) {
            this.buttonTarget.textContent = this.visible ? 'Masquer les mots de passe' : 'Afficher les mots de passe';
        }
    }
}
