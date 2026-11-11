// Admin bundle: Bootstrap 5, colour mode, Font Awesome icons, charts, the editor and copy buttons
import './bootstrap5';
import '@fortawesome/fontawesome-free/css/all.css';
import { initAdminCharts } from './admin-charts';
import { initAdminEditors } from './admin-editor';
import { initCopyButtons } from './admin-copy';

initAdminCharts();
initAdminEditors();
initCopyButtons();
