// Admin bundle: Bootstrap 5, colour mode, Font Awesome icons, charts, the editor, copy buttons and the timetable grid
import './bootstrap5';
import '@fortawesome/fontawesome-free/css/all.css';
import { initAdminCharts } from './admin-charts';
import { initAdminEditors } from './admin-editor';
import { initCopyButtons } from './admin-copy';
import { initTimetableGrid } from './admin-timetable';

initAdminCharts();
initAdminEditors();
initCopyButtons();
initTimetableGrid();
