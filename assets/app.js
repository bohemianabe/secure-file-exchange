/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'bootstrap';
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
            duration: 2000,
            dismissible: true,
        },
    ],
});

// ag: attached notyf to the window object so I can access it globally.
window.notyf = notyf;

// notyf.open({
//     type: 'warning',
//     message: 'Send us <b>an email</b> to get support',
// });
// notyf.success('Operation completed!');

// console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', () => {
    const el = document.querySelector('#myTable');
    if (el) {
        $(el).DataTable({
            // simple defaults; customize later
            pageLength: 10,
            order: [[0, 'asc']], // default sort first column
        });
    }
});
