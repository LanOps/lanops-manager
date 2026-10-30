// Admin bundle: Bootstrap 5, colour mode, Font Awesome icons, charts and the editor
import './bootstrap5';
import '@fortawesome/fontawesome-free/css/all.css';
import { initAdminCharts } from './admin-charts';
import { initAdminEditors } from './admin-editor';

initAdminCharts();
initAdminEditors();
