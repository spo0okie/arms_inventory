document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dokuwiki-toolbar button').forEach(button => {
        button.addEventListener('click', function() {
            const textarea = document.querySelector('.dokuwiki-textarea');
            const command = this.getAttribute('data-command');
            const startPos = textarea.selectionStart;
            const endPos = textarea.selectionEnd;
            const selectedText = textarea.value.substring(startPos, endPos);

            let newText;
            switch (command) {
                case 'bold':
                    newText = `**${selectedText}**`;
                    break;
                case 'italic':
                    newText = `//${selectedText}//`;
                    break;
                case 'heading':
                    newText = `==== ${selectedText} ====`;
                    break;
                case 'link':
                    newText = `[[${selectedText}|Link Title]]`;
                    break;
                case 'code':
                    newText = `''${selectedText}''`;
                    break;
                default:
                    newText = selectedText;
            }

            textarea.value = textarea.value.substring(0, startPos) + newText + textarea.value.substring(endPos);
            textarea.focus();
        });
    });
});