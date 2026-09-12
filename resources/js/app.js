import './bootstrap';

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import $ from 'jquery';
window.$ = window.jQuery = $;

import 'datatables.net-bs5';

import Swal from 'sweetalert2';
window.Swal = Swal;

import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;
