// Public site bundle: Bootstrap 5, colour mode and the Roboto font
import './bootstrap5';
import '@fontsource/roboto/300.css';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/700.css';

// Gallery lightbox, only fetched on pages with .spotlight links
if (document.querySelector('.spotlight')) {
    import('spotlight.js/dist/spotlight.bundle.js');
}
