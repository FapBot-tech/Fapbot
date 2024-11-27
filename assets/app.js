import { registerReactControllerComponents } from '@symfony/ux-react';
import './bootstrap.js';
import './app.scss';

import "quill/dist/quill.snow.css";
import Quill from "quill";

global.bootstrap = require('bootstrap');

let autoCompleteUsername = require('./js/auto-complete-username');
let searchableDropdown = require('./js/searchable-dropdown');
let combinableText = require('./js/combineable-text');
let disableButton = require('./js/disable-button');
let searchUser= require('./js/search-user');
let prefilledReason = require('./js/prefilled-reason');
let problematicUser = require('./js/problematic-user');
let tooltips = require('./js/tooltips');
let filterBlock = require('./js/filter-block');
let fetchChatImage = require('./js/fetchChatImage');
let DisableDeleteButton = require('./js/delete-disable-button');
let channelMessageSearch = require('./js/channel-message-search');
let zoomableImage = require('./js/zoomable-image');
let toggleClass = require('./js/toggle-class');

document.addEventListener("DOMContentLoaded", () =>{
    autoCompleteUsername.init();
    searchUser.init();
    searchableDropdown.init();
    combinableText.init(searchUser);
    disableButton.init();
    prefilledReason.init();
    problematicUser.init();
    tooltips.init();
    filterBlock.init();
    fetchChatImage.init();
    DisableDeleteButton.init();
    channelMessageSearch.init();
    zoomableImage.init();
    toggleClass.init();
    problematicUser.submitButton();

    const toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike', 'link'],        // toggled buttons
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

        [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
        [{ 'color': [] }, {'background': [] }],
        ['clean']                                         // remove formatting button
    ];

    let editor =document.querySelector('#editor');
    let targetInput = document.getElementById(editor.dataset.target);

    const quill = new Quill(editor, {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });
    quill.clipboard.dangerouslyPasteHTML(targetInput.value);
    quill.on('editor-change', (delta, oldDelta, source) => {
        targetInput.value = quill.getSemanticHTML().replace('target="_blank"', '');
    });
});
registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));