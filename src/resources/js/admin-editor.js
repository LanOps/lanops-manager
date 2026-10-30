// Summernote rich text editor for .wysiwyg-editor textareas. Summernote needs
// jQuery, so both are only fetched on admin pages that have an editor.
import { Dropdown, Modal, Tooltip } from 'bootstrap';

export async function initAdminEditors() {
    const textareas = document.querySelectorAll('.wysiwyg-editor');
    if (!textareas.length) {
        return;
    }

    const { default: $ } = await import('jquery');
    window.jQuery = window.$ = $;

    // Summernote's Bootstrap 5 build opens its dialogs and tooltips through the
    // jQuery plugins, which Bootstrap only adds when jQuery is there at page load
    for (const plugin of [Dropdown, Modal, Tooltip]) {
        $.fn[plugin.NAME] = plugin.jQueryInterface;
        $.fn[plugin.NAME].Constructor = plugin;
    }

    await import('summernote/dist/summernote-bs5.css');
    await import('summernote/dist/summernote-bs5.js');

    $(textareas).summernote({
        height: 300,
    });
}
