module.exports = {
    init: () => {
        const modal = document.getElementById('problematicUserModal');

        if (modal === null) return;

        const username = modal.dataset.username;
        if (username === undefined) return;

        let xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;

            let json = JSON.parse(xhr.responseText);
            const resultModal = new bootstrap.Modal(modal);
            document.getElementById('SearchResultError').innerText = '';
            document.getElementById('SearchResultError').classList = 'd-none';

            if (json.error !== undefined) {
                document.getElementById('SearchResultError').innerText = json.error;
                document.getElementById('SearchResultError').classList = 'fw-bold d-block text-danger mb-4';

                resultModal.show();

                return;
            }

            let muteTable = document.getElementById('SearchResultMuteTable');
            muteTable.innerHTML = '<thead>' +
                '    <tr>\n' +
                '        <th>Reason</th>' +
                '        <th>Time ago</th>' +
                '        <th>Duration</th>' +
                '    </tr>' +
                ' </thead>'


            let muteCount = document.getElementById('MuteCount');

            json.mutes.forEach((mute) => {
                let row = `<tr><td>${mute.reason}</td><td>${mute.timeAgo}</td><td>${mute.duration}</td></tr>`;
                muteTable.innerHTML += row;
            });

            muteCount.innerText = json.mute_count;

            let warningTable = document.getElementById('SearchResultWarningTable');
            warningTable.innerHTML = '<thead>' +
                '    <tr>\n' +
                '        <th>Reason</th>' +
                '        <th>Time ago</th>' +
                '    </tr>' +
                ' </thead>'

            let warningCount = document.getElementById('WarningCount');

            json.warnings.forEach((warning) => {
                let row = `<tr><td>${warning.reason}</td><td>${warning.timeAgo}</td></tr>`;
                warningTable.innerHTML += row;
            });

            warningCount.innerText = json.warning_count;

            if (json.previous_deactivation) {
                let deactivationElement = document.getElementById('PreviousDeactivation');
                deactivationElement.classList.remove('d-none');

                let warningMuteTrigger = document.getElementById('WarningMuteTrigger');
                warningMuteTrigger.classList.add('d-none');

                let deactivateLink = document.getElementById('DeactivateLink');
                deactivateLink.classList.remove('d-none');
            }

            resultModal.show();
        };
        xhr.open('GET', 'https://fapbot.tech/chat_user/' + username + '/json', true);
        xhr.send(null);

        // Prepare button
        const button = document.getElementById('problematicConfirm');
        button.addEventListener('click', () => {
            window.history.replaceState({}, null, '?problematicOverride=true');
        });
    },

    submitButton() {
        // Prepare submit button
        const submitButton = document.getElementById('problematicConfirmSubmit');
        if (submitButton === null) return;

        submitButton.addEventListener('click', (event) => {
            event.preventDefault();

            window.history.replaceState({}, null, '?problematicOverride=true');
            event.target.form.submit();
        })
    }
}