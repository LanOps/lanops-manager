// "Copy" buttons: <button data-copy-target="#input-id">, copies that input's value
export function initCopyButtons() {
    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = document.querySelector(button.dataset.copyTarget);
            if (!input) {
                return;
            }
            const label = button.innerHTML;
            try {
                await navigator.clipboard.writeText(input.value);
            } catch (e) {
                // Older browsers, or a page not served over https: copy from the selected text instead
                input.select();
                document.execCommand('copy');
            }
            button.innerHTML = '<i class="fa fa-check fa-fw"></i> Copied';
            setTimeout(() => {
                button.innerHTML = label;
            }, 2000);
        });
    });
}
