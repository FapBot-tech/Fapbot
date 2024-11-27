module.exports = {
    init: () => {
        let buttons = document.querySelectorAll('.user-search');

        buttons.forEach( button => {
            button.addEventListener('click', () => {
                let field = document.querySelector('input[data-lookup="'+button.dataset.target+'"]');

                let xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        let json = JSON.parse(xhr.responseText);
                        const resultModal = new bootstrap.Modal(document.getElementById('searchResultModal'));
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

                        resultModal.show();
                    }
                }

                if (field.value?.trim() !== '') {
                    xhr.open('GET', 'https://fapbot.tech/chat_user/' + field.value + '/json', true);
                    xhr.send(null);
                }
            });
        });
    }
}