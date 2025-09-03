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

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', () => {
  console.log('data table here');
  const el = document.querySelector('#myTable');
  if (el) {
    $(el).DataTable({
      // simple defaults; customize later
      pageLength: 10,
      order: [[0, 'asc']], // default sort first column
    });
  }
});
