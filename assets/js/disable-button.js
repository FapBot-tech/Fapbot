module.exports = {
    init: () => {
        let button = document.querySelectorAll('.btn-disable-click');

        button.forEach( el => {
            el.classList.remove('disabled');

            el.addEventListener('click', () => {
                let isDisabled = el.disabled ?? false;
                el.disabled = !isDisabled;

                if (!isDisabled)
                    el.classList.add('disabled');
                else
                    el.classList.remove('disabled');

                let form = el.form ?? el.parentElement.form ?? el.parentElement.parentElement;

                form.submit();
            });
        });
    }
}