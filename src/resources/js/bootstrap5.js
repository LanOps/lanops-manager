// Bootstrap 5 JS (bundled with Popper) and the colour mode toggle.
// Shared by the public and admin bundles.
import * as bootstrap from 'bootstrap';
import { initColorMode } from './color-mode';

window.bootstrap = bootstrap;
initColorMode();
