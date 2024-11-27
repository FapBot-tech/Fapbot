module.exports = {
    init: () => {
        let inputs = document.querySelectorAll('.prefilled-reason select');

        inputs.forEach( input => {
           const options = JSON.parse(input.dataset.options);

           options.forEach( option => {
               input.append(new Option(option.name, option.reason));
           });

           input.addEventListener('change', () => {
               const reasonInput = document.querySelector('textarea[name="'+input.name.replace('-options', '')+'"]');

               reasonInput.value = input.options[input.selectedIndex].value;
           });
        });
    }
}