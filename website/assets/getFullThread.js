// Utilities
function escapeHtml(str){
    if (str == null) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

function getQueryId(){
    try { return parseInt(new URLSearchParams(window.location.search).get('id')||'0',10);} catch(e){return 0;}
}

function getFileType(filename) {
    if (!filename) return 'unknown';
    const ext = filename.split('.').pop().toLowerCase();
    if (['jpeg','jpg','png','gif','webp'].includes(ext)) return 'image';
    if (['mp4','webm'].includes(ext)) return 'video';
    if (ext === 'mp3' || ext === 'wav' || ext === 'ogg') return 'audio';
    if (ext === 'pdf') return 'pdf';
    return 'unknown';
}

async function fetchThread(threadId, commentPage){
    const form = new FormData();
    form.append('thread_id', String(threadId));
    form.append('comment_page', String(commentPage));
    const res = await fetch('/requests/getThread.php', { method: 'POST', body: form });
    if (!res.ok) throw new Error('Network error '+res.status);
    return await res.json();
}

// Render helpers
function renderMedia(container, media){
    container.innerHTML = '';
    if (!media) return;
    const type = getFileType(media);
    const src = `/uploads/${media}`;
    if (type === 'image'){
        container.innerHTML = `
            <a href="${src}" target="_blank">
                <img src="${src}" class="img-fluid  m-1 " style="" alt="media">
            </a>
        `;
    } else if (type === 'video'){
        container.innerHTML = `<video class="mx-3 " src="${src}" controls ></video>`;
    } else if (type === 'audio'){
        const thumbName = `thumb_${media.replace(/\.[^.]+$/, '')}.jpg`;
        const thumbSrc = `/uploads/${thumbName}`;
        container.innerHTML = `
            <a href="${thumbSrc}" target="_blank">
                <img src="${thumbSrc}" class="mx-3  mb-2 img-fluid" alt="audio thumbnail">
            </a>
            <audio src="${src}" controls class="mx-3  w-75"></audio>`;
    } else if (type === 'pdf'){
        container.innerHTML = `<a href="${src}" target="_blank" rel="noopener">Open attachment</a>`;
    }
}

function buildMarkdownHtml(text){
    // Escape to mitigate XSS, then render as markdown
    const safe = escapeHtml(text || '');
    try {
        return marked.parse(safe);
    } catch(e) {
        return `<pre>${safe}</pre>`;
    }
}

function renderWithReadMore(text, limit=1000){
    const full = String(text || '');
    const isLong = full.length > limit;
    const shortText = isLong ? full.slice(0, limit) : full;
    const shortHtml = buildMarkdownHtml(shortText + (isLong ? '...' : ''));
    const fullHtml = buildMarkdownHtml(full);

    const wrapper = document.createElement('div');
    wrapper.className = "p-1 card";
    const content = document.createElement('div');
    content.innerHTML = shortHtml;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-link p-0';
    btn.textContent = isLong ? 'Read more' : '';
    if (!isLong) {
        wrapper.appendChild(content);
        return wrapper;
    }

    let expanded = false;
    btn.addEventListener('click', ()=>{
        expanded = !expanded;
        content.innerHTML = expanded ? fullHtml : shortHtml;
        btn.textContent = expanded ? 'Read less' : 'Read more';
    });

    wrapper.appendChild(content);
    wrapper.appendChild(btn);
    return wrapper;
}

function deriveTitleFromContent(thread){
    const name = thread && thread.name ? thread.name : 'Anonymous';
    const board = thread && thread.board_name;
    return `Thread by ${name} on ${board} Board!`;
}

// Main rendering
const elTitle = document.getElementById('thread-title');
const elDate = document.getElementById('thread-date');
const elMedia = document.getElementById('thread-media');
const elBody = document.getElementById('thread-body');
const elComments = document.getElementById('comments');
const elCommentsMore = document.getElementById('comments-more');

let COMMENT_PAGE = 1;
let HAS_MORE = false;
let THREAD_DATA = null;
const THREAD_ID = getQueryId();

function appendComments(comments){
    const frag = document.createDocumentFragment();
    comments.forEach(c => {
        const wrap = document.createElement('div');
        wrap.className = 'media mb-3';
        const body = document.createElement('div');
        body.className = 'media-body';
        const h4 = document.createElement('p');
        h4.className = 'media-heading p-0 m-0 fw-bold';
        h4.textContent = c.name || 'Anonymous';
        const h6 = document.createElement('small');
        h6.className = 'text-muted';
        h6.textContent = c.created_at || 'idk';
        const content = renderWithReadMore(c.body, 1000);
        body.appendChild(h4);
        body.appendChild(h6);
        body.appendChild(content);
        if (c.media){
            const mediaDiv = document.createElement('div');
            renderMedia(mediaDiv, c.media);
            mediaDiv.className = 'mt-2 h-50';
            body.appendChild(mediaDiv);
        }
        wrap.appendChild(body);
        frag.appendChild(wrap);
        const hr = document.createElement('hr');
        frag.appendChild(hr);
    });
    elComments.appendChild(frag);
}

async function loadComments(){
    try {
        elCommentsMore.innerHTML = '<p>Loading...</p>';
        const data = await fetchThread(THREAD_ID, COMMENT_PAGE);
        const comments = Array.isArray(data.comments) ? data.comments : [];
        appendComments(comments);
        HAS_MORE = !!(data.pagination && data.pagination.has_more);
        if (HAS_MORE){
            elCommentsMore.innerHTML = '<button id="btn-more-comments" class="btn btn-secondary">Load more comments</button>';
            const btn = document.getElementById('btn-more-comments');
            btn.onclick = () => { COMMENT_PAGE++; loadComments(); };
        } else {
            elCommentsMore.innerHTML = '<p>No more comments.</p>';
        }
    } catch(e){
        console.error(e);
        elCommentsMore.innerHTML = '<p class="text-danger">Failed to load comments.</p>';
    }
}

async function init(){
    if (!THREAD_ID){
        if (elTitle) elTitle.textContent = 'No post specified';
        return;
    }
    try {
        // First page to get thread and first comments
        const data = await fetchThread(THREAD_ID, 1);
        if (data && data.not_found){
            if (elTitle) elTitle.textContent = 'There is no post with the given id.';
            document.title = 'Post not found';
            return;
        }
        THREAD_DATA = data.thread || {};
        // Title and meta
        const derived = deriveTitleFromContent(THREAD_DATA);
        if (elTitle) elTitle.textContent = derived;
        const board_id = THREAD_DATA && THREAD_DATA.board_id;
        if (elTitle) elTitle.innerHTML += `  <a href="/board.php?board_id=${board_id}">visit</a>`;
        document.title = derived;
        if (elDate) elDate.textContent = THREAD_DATA.created_at || '';
        // Media
        if (elMedia) renderMedia(elMedia, THREAD_DATA.media);
        // Body with markdown + read more
        if (elBody){
            elBody.innerHTML = '';
            elBody.appendChild(renderWithReadMore(THREAD_DATA.body || '', 1000));
        }
        // Comments
        elComments.innerHTML = '';
        const comments = Array.isArray(data.comments) ? data.comments : [];
        appendComments(comments);
        COMMENT_PAGE = 1;
        HAS_MORE = !!(data.pagination && data.pagination.has_more);
        if (HAS_MORE){
            elCommentsMore.innerHTML = '<button id="btn-more-comments" class="btn btn-secondary">Load more comments</button>';
            document.getElementById('btn-more-comments').onclick = () => { COMMENT_PAGE++; loadComments(); };
        } else {
            elCommentsMore.innerHTML = '<p>No more comments.</p>';
        }
    } catch (e){
        console.error(e);
        if (elTitle) elTitle.textContent = 'Failed to load post';
    }
}

init();
