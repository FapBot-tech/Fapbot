let DisableDeleteButton = require('./delete-disable-button');
let zoomableImage = require('./zoomable-image');

module.exports = {
    fillTarget(target, channel, messages) {
        const targetElement = document.querySelector(target);

        let table = '<div>' +
            '<table class="table table-striped">' +
            '<thead>' +
            '<tr>' +
            '<th>Message</th>' +
            '<th>Time ago</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';


        messages.forEach( message => {
            if (message.isDeleted) return;

            let row = '<tr class="position-relative" id="row-'+message._id+'"><td>';

            if (message.publicUrl !== null || message.image !== null && message.image.startsWith('http')) {
                const imageUrl = message.publicUrl ?? message.image;
                row += "<div>" + message.msg?.replace(imageUrl, '') + '<br/>'
                    + '<img loading="lazy" src="'+imageUrl+'" class="img-size img-zoomable" alt="'+message.msg+'"/></div>';
            } else {
                row += message.msg;
            }

            row += '</td><td>'+message.timeAgo+'';

            row += '<br/><button type="button" class="btn btn-danger btn-delete-click" data-href="/admin/message_activity/none/delete/'+message.id+'/'+channel+'" data-element="row-'+message._id+'?json=true">Delete</button></td>';

            row += '</tr>';

            table += row;
        });

        table +=
            '</tbody>' +
            '</table>' +
            '</div>';

        targetElement.innerHTML = table;
    },

    init: () => {
        let channelElements = document.querySelectorAll('.message-card');

        channelElements.forEach( async (element) => {
            const channelID = element.dataset.channel;
            const username = element.dataset.username;
            const target = element.dataset.target;

            fetch('https://fapbot.tech/message_activity/'+username+'/'+channelID, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                priority: "low"
            })
            .then((response) => {
                if (response.ok) {
                    return response.json();
                }

                element.classList.add('bg-danger-subtle');
                const body= element.querySelector('#channel-'+channelID);
                body.innerHTML = '<div class="d-flex jusitfy-content-center flex-column">' +
                    '<h2 class="w-100">Error</h2>' +
                    '<small>There was an error fetching the messages</small>';
            })
            .then(json => {
                if (json.length === 0) {
                    element.parentElement.style.display = 'none';
                    return;
                }

                const body= element.querySelector('#channel-'+channelID);
                body.innerHTML = '<div class="d-flex jusitfy-content-center flex-column">' +
                    '<h2 class="w-100">Messages: '+json.length+'</h2>' +
                    '<small>It only searches the last 100 messages of a channel</small>';

                if (target !== undefined) {
                    body.innerHTML += '<a data-bs-toggle="modal" data-bs-target="#channel-messages-'+channelID+'" class="btn btn-primary mt-3">See messages</a>';

                    module.exports.fillTarget(target, channelID, json);
                    DisableDeleteButton.init();
                    zoomableImage.init();
                }

                body.innerHTML += "</div>";
            });
        });

        zoomableImage.init();
    }
}