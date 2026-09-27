/**
 * CMS Forum – Thread erstellen (Umfrage-Optionen, ähnliche Threads).
 * Ausgelagert aus dem Template, damit es unter der 365CMS-CSP läuft.
 */
document.getElementById('add-poll-option')?.addEventListener('click', function() {
    const container = document.querySelector('.cmsforum-poll-options');
    const count = container.querySelectorAll('input').length;
    if (count >= 10) return;
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'poll_options[]';
    input.className = 'cmsforum-input';
    input.maxLength = 200;
    input.placeholder = (this.getAttribute('data-option-prefix') || 'Option') + ' ' + (count + 1);
    container.appendChild(input);
});

(() => {
    const titleInput = document.getElementById('thread-title');
    const panel = document.getElementById('cmsforum-similar-threads');
    if (!titleInput || !panel) return;

    const list = panel.querySelector('.cmsforum-similar-threads__list');
    const apiUrl = panel.getAttribute('data-api-url');
    const forumId = panel.getAttribute('data-forum-id');
    if (!list || !apiUrl || !forumId) return;

    let timer = null;
    titleInput.addEventListener('input', () => {
        const q = titleInput.value.trim();
        window.clearTimeout(timer);

        if (q.length < 4) {
            panel.hidden = true;
            list.innerHTML = '';
            return;
        }

        timer = window.setTimeout(async () => {
            const url = new URL(apiUrl, window.location.origin);
            url.searchParams.set('forum_id', forumId);
            url.searchParams.set('q', q);

            try {
                const res = await fetch(url.toString(), { credentials: 'same-origin' });
                const data = await res.json();
                const threads = Array.isArray(data?.threads) ? data.threads : [];

                list.innerHTML = '';
                if (!threads.length) {
                    panel.hidden = true;
                    return;
                }

                for (const thread of threads) {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = thread.url || '#';
                    a.textContent = thread.title || '';
                    li.appendChild(a);
                    list.appendChild(li);
                }
                panel.hidden = false;
            } catch (e) {
                panel.hidden = true;
            }
        }, 350);
    });
})();
