<style>
    #chatBox::-webkit-scrollbar { width: 6px; }
    #chatBox::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    .chat-bg { background-color: #e5ddd5; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.9; }
    .reply-preview { border-left: 4px solid #00a884; background: #f0f2f5; font-size: 0.85rem; }
    .file-preview { border-left: 4px solid #0d6efd; background: #e7f1ff; font-size: 0.85rem; }
</style>

<div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100 d-flex flex-column">
    
    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between sticky-top z-3">
        <div class="d-flex align-items-center">
            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                <i class="fas fa-users fa-lg"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0">Ruang Komando (Live)</h6>
                <small class="text-success"><i class="fas fa-circle fa-xs me-1"></i>Online</small>
            </div>
        </div>
        <button onclick="loadChat()" class="btn btn-light rounded-circle text-secondary" title="Refresh"><i class="fas fa-sync-alt"></i></button>
    </div>

    <div class="card-body chat-bg p-4 overflow-auto flex-grow-1" id="chatBox">
        <div class="text-center py-5">
            <div class="spinner-border text-secondary" role="status"></div>
            <p class="text-muted small mt-2">Memuat percakapan...</p>
        </div>
    </div>

    <div id="previewArea" class="px-3 pt-2 bg-white border-top d-none">
        
        <div id="replyBox" class="reply-preview p-2 rounded mb-2 d-none justify-content-between align-items-center">
            <div>
                <span class="d-block fw-bold text-primary small" id="replyName">Nama</span>
                <span class="text-muted text-truncate d-block small" style="max-width: 300px;" id="replyText">Pesan...</span>
            </div>
            <button onclick="cancelReply()" class="btn btn-sm text-secondary"><i class="fas fa-times"></i></button>
        </div>

        <div id="fileBox" class="file-preview p-2 rounded mb-2 d-none justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-file-upload text-primary"></i>
                <span class="fw-bold text-dark small" id="fileName">file.jpg</span>
            </div>
            <button onclick="cancelFile()" class="btn btn-sm text-secondary"><i class="fas fa-times"></i></button>
        </div>

    </div>

    <div class="card-footer bg-white p-3 border-top">
        <form id="formChat" class="d-flex align-items-center gap-2" enctype="multipart/form-data">
            <input type="hidden" id="replyIdInput" name="reply_id">
            
            <input type="file" id="fileInput" name="file" class="d-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
            
            <button type="button" class="btn btn-light rounded-circle text-secondary" onclick="document.getElementById('fileInput').click()" title="Kirim Foto/Dokumen">
                <i class="fas fa-paperclip"></i>
            </button>

            <input type="text" id="pesanInput" name="message" class="form-control rounded-pill bg-light border-0 py-2 px-4" placeholder="Ketik pesan..." autocomplete="off">
            
            <button type="submit" class="btn btn-primary rounded-circle p-2 shadow-sm" style="width: 45px; height: 45px;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    const input = document.getElementById('pesanInput');
    const fileInput = document.getElementById('fileInput');
    
    // Preview Elements
    const previewArea = document.getElementById('previewArea');
    const replyBox = document.getElementById('replyBox');
    const fileBox = document.getElementById('fileBox');
    
    // Reply Elements
    const replyIdInput = document.getElementById('replyIdInput');
    const replyName = document.getElementById('replyName');
    const replyText = document.getElementById('replyText');
    const fileName = document.getElementById('fileName');

    // 1. EVENT SAAT FILE DIPILIH
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileName.innerText = this.files[0].name;
            fileBox.classList.remove('d-none');
            fileBox.classList.add('d-flex');
            previewArea.classList.remove('d-none');
        }
    });

    function cancelFile() {
        fileInput.value = '';
        fileBox.classList.add('d-none');
        fileBox.classList.remove('d-flex');
        checkPreviewEmpty();
    }

    // 2. LOAD CHAT
    function loadChat() {
        fetch('ajax/chat_handler.php?act=load')
            .then(res => res.text())
            .then(data => {
                const isBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 100;
                chatBox.innerHTML = data;
                if (isBottom) chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    // 3. KIRIM PESAN (DENGAN FILE)
    document.getElementById('formChat').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Cek apakah ada isi (teks atau file)
        if (input.value.trim() === '' && fileInput.files.length === 0) return;

        const fd = new FormData(this);
        fd.append('act', 'send');

        // Reset Input Visual biar cepat
        input.value = '';
        cancelReply();
        cancelFile();
        
        fetch('ajax/chat_handler.php', { method: 'POST', body: fd })
            .then(res => res.text())
            .then(data => {
                loadChat();
                setTimeout(() => chatBox.scrollTop = chatBox.scrollHeight, 200);
            });
    });

    // 4. REPLY FUNCTION
    function replyMessage(id, name, text) {
        replyIdInput.value = id;
        replyName.innerText = name;
        replyText.innerText = text;
        
        replyBox.classList.remove('d-none');
        replyBox.classList.add('d-flex');
        previewArea.classList.remove('d-none');
        input.focus();
    }

    function cancelReply() {
        replyIdInput.value = '';
        replyBox.classList.add('d-none');
        replyBox.classList.remove('d-flex');
        checkPreviewEmpty();
    }

    function checkPreviewEmpty() {
        if (replyBox.classList.contains('d-none') && fileBox.classList.contains('d-none')) {
            previewArea.classList.add('d-none');
        }
    }
    
    function deleteMessage(id) {
        if(confirm("Hapus pesan ini?")) {
            const fd = new FormData();
            fd.append('act', 'delete');
            fd.append('id', id);
            fetch('ajax/chat_handler.php', { method: 'POST', body: fd }).then(() => loadChat());
        }
    }

    loadChat();
    setInterval(loadChat, 2000);
</script>