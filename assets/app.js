/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import * as bootstrap from 'bootstrap';
import 'datatables.net-bs5';
import $ from 'jquery';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const notyf = new Notyf({
    duration: 4000,
    position: {
        x: 'right',
        y: 'top',
    },
    types: [
        {
            type: 'warning',
            background: 'orange',
            icon: {
                className: 'material-icons',
                tagName: 'i',
                text: 'warning',
            },
        },
        {
            type: 'error',
            background: 'indianred',
            duration: 4000,
            dismissible: true,
        },
    ],
});

// ag: logic for global dynamic modals to open
$(document).on('click', '[data-dynamic-load]', function (e) {
    let this_component = $(e.currentTarget);
    let this_modal = $(this_component.data('bs-target'));
    let this_target = this_component.attr('href') ? this_component.attr('href') : '??';
    let modal_title = this_component.attr('data-modal-title');

    if (this_modal.find('.modal-title').length == 0) $('.modal-title').text(modal_title);

    $.ajax({
        url: this_target,
        type: 'GET',
        data: {},
        async: false,
        success: function (data) {
            $('.default-data-insert').html(data);
            let modalEl = document.getElementById('defaultTemplateModal');
            let bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }, //end success
    }); //end $.ajax
});

// ag: attached notyf to the window object so I can access it globally.
window.notyf = notyf;

document.getElementById('clear-logo').addEventListener('click', function () {
    const fileInput = document.getElementById('company-logo-input');
    fileInput.value = ''; // reset the input
});

// notyf.open({
//     type: 'warning',
//     message: 'Send us <b>an email</b> to get support',
// });
// notyf.success('Operation completed!');

// console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// document.addEventListener('DOMContentLoaded', () => {
//     console.log('wtf');
//     const el = document.querySelector('#myTable');
//     if (el) {
//         $(el).DataTable({
//             // simple defaults; customize later
//             pageLength: 10,
//             order: [[0, 'asc']], // default sort first column
//         });
//     }
// });
