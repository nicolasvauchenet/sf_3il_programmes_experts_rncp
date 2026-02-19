import {Controller} from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        this.element.addEventListener('click', this.close)

        setTimeout(() => {
            this.element.remove()
        }, 5000)
    }

    close(e) {
        e.preventDefault();
        e.currentTarget.remove()
    }
}
