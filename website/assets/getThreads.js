const BOARD_ID = (() => { try { return parseInt(new URLSearchParams(window.location.search).get('board_id')||'0',10);} catch(e){return 0;} })();
async function get_posts(keyword, page) {
    try {
        const form = new FormData();
        form.append('board_id', String(BOARD_ID));
        form.append('page', String(page));
        if (keyword) form.append('keyword', String(keyword));
        const response = await fetch("/requests/getPosts.php", {
            method: "POST",
            body: form
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const json = await response.json();
        return json;
    } catch (error) {
        console.error("Error fetching posts:", error);
        return [];
    }
}
let more = document.getElementById("more");
let feed = document.getElementById("feed");
let loadmore = 1; // 1 = can load more, 0 = reached end
let isLoading = false;

function escapeHtml(str){
    if (str == null) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

function createCardHTML(post){
    const threadId = post.thread_id;
    const name = post.name || 'Anonymous';
    const body = post.body || '';
    const date = post.created_at || '';
    const comments = typeof post.comments_count === 'number' ? post.comments_count : parseInt(post.comments_count || '0', 10) || 0;
    let media = post.media || '';
    if (media !== '' && (media.slice(-3) === 'mp4' || media.slice(-3) === 'mp3')){
        media = media.substring(0, media.length-3) + 'jpg';
        console.log(media);
    }
    let mediaHtml = '';
    const uploadsBase = '/uploads/';
    const imgSrcThumb = uploadsBase + 'thumb_' + media;
    const imgSrc = uploadsBase + media;
    mediaHtml = `<img class="card-img-top" src="${imgSrcThumb}" alt="">`;
    const bodySnippet = escapeHtml(body).slice(0, 220);
    const commentsLabel = `${comments} ${comments === 1 ? 'comment' : 'comments'}`;
    const meta = date ? `${escapeHtml(date)} - ${commentsLabel}` : commentsLabel;
    return `
    <a href="/thread.php?id=${encodeURIComponent(threadId)}" class="text-reset text-decoration-none">
      <div class="card mb-4 hover-shadow h-100">
        ${mediaHtml}
        <div class="card-body">
          <h5 class="card-title mb-2">${escapeHtml(name)}</h5>
          <p class="card-text">${bodySnippet}</p>
          <p class="card-text"><small class="text-muted">${meta}</small></p>
        </div>
      </div>
    </a>`;
}

async function loadPosts(keyword, page) {
    if (isLoading || loadmore === 0) return;
    isLoading = true;
    if (more) more.innerHTML = '<p>Loading...</p>';
    const data = await get_posts(keyword, page);
    const posts = Array.isArray(data) ? data : (data && Array.isArray(data.posts) ? data.posts : []);
    if (posts.length !== 0) {
        let html = '';
        posts.forEach(post => {
            html += createCardHTML(post);
        });
        if (feed) feed.insertAdjacentHTML('beforeend', html);
        if (more) more.innerHTML = '';
    } else {
        if (more) more.innerHTML = `<p>No more threads.</p>`;
        loadmore = 0;
    }
    isLoading = false;
}

let images = [
    'jpeg',
    'jpg',
    'png',
    'gif',
    'webp'
];

let videos = [
    'mp4',
    'webm'
];

function getFileType(filename) {
    console.log(filename);
    if (!filename) {
        return 'unknown';
    }
    const fileExtension = filename.split('.').pop().toLowerCase();
    if (images.includes(fileExtension)) {
        return 'image';
    } else if (videos.includes(fileExtension)) {
        return 'video';
    } else if (fileExtension === 'pdf') {
        return 'pdf';
    } else if (fileExtension === 'mp3') {
        return 'audio';
    } else {
        return 'unknown';
    }
}

// console.log(getFileType("test.jpg"));

loadPosts("", 1);

let page = 1;
let search = document.getElementById("search");
let clear = document.getElementById("clear");
let searchBtn = document.getElementById("search-btn");

if (searchBtn) {
    searchBtn.addEventListener("click", () => {
        if (feed) feed.innerHTML = "";
        if (clear) clear.style.display = "inline-block";
        loadmore = 1;
        page = 1;
        const term = search ? search.value : '';
        loadPosts(term, page);
    });
}

if (clear) {
    clear.addEventListener("click", () => {
        if (feed) feed.innerHTML = "";
        if (search) search.value = "";
        clear.style.display = "none";
        page = 1;
        loadmore = 1;
        if (more) more.innerHTML = `<p>Load more...</p>`;
        loadPosts("", page);
    });
}

function executeOnPageEnd() {
    if (loadmore === 1) {
        page++;
        const term = search ? search.value : '';
        loadPosts(term, page);
    }
}

window.addEventListener('scroll', function() {
    const scrollPosition = window.innerHeight + window.scrollY;
    const documentHeight = document.documentElement.scrollHeight;
    if (scrollPosition >= documentHeight - 5) { // -5 for a small buffer
        executeOnPageEnd();
    }
});