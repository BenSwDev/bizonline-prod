document.addEventListener('DOMContentLoaded', function () {
    console.log("issue-reporter.js loaded.");

    const siteLang = (document.documentElement.lang || 'he').toLowerCase();
    const isRtl = ['he', 'ar'].includes(siteLang);

    const translations = {
        he: {
            reportBugBtn: "דווח על תקלה",
            title: "איך אפשר לעזור?",
            issueTypeLabel: "מה ברצונך לעשות?",
            issueTypes: [
                { value: "דיווח על תקלה", text: "דיווח תקלה" },
                { value: "בעיית תצוגה", text: "בעיית תצוגה" },
                { value: "תרגום שגוי", text: "תרגום שגוי" },
                { value: "הצעות ובקשות", text: "בקשה / הצעה" },
                { value: "אחר", text: "אחר" }
            ],
            problemLabel: "נושא",
            descriptionLabel: "תיאור",
            attachFilesLabel: "צרף תמונות",
            dropZoneText: "לחץ או גרור תמונות לכאן\n(ניתן גם להדביק Ctrl+V...)",
            submitBtn: "שליחה",
            successMessage: "הדיווח נשלח בהצלחה!",
            removeBtn: "מחק",
            errorSubmitting: "שגיאה בשליחת הדיווח:",
            screenRecording: "הקלטת מסך",
            screenCapturing: "צילום מסך",
            myIssuesBtnShow: "הצג פניות קודמות",
            myIssuesBtnHide: "הסתר פניות קודמות",
            maxImagesError: "ניתן להעלות עד 4 תמונות בלבד",
            noIssues: "אין בעיות",
            editComment: "ערוך הערה",
            deleteComment: "מחק הערה",
            confirmDelete: "האם אתה בטוח?",
            addComment: "הוסף הערה",
            updateSuccess: "עודכן בהצלחה",
            issueType: "סוג בעיה",
            status: "סטטוס",
            titleCol: "כותרת",
            descriptionCol: "תיאור",
            images: "תמונות",
            comments: "הערות",
            actions: "פעולות",
            delete: "סגור (מחק)",
            save: "שמור",
            noComments: "אין הערות",
            myIssuesNotDoneTab: "פעילים",
            myIssuesDoneTab: "סגורים",
            pasteImage: "הדבק תמונה",
            editIssue: "ערוך",
            cancelEdit: "בטל",
            reopen: "פתח מחדש"
        }
    };
    const t = translations[siteLang] || translations.he;
    const direction = isRtl ? 'rtl' : 'ltr';
    const MAX_IMAGES = 4;

    // Build the main widget container
    const container = document.createElement('div');
    container.innerHTML = `
        <div class="issue-reporter-widget" dir="${direction}">
            <button class="report-bug-button" id="reportBugBtn">${t.reportBugBtn}</button>
            <div class="reporter-panel" id="reporterPanel">
                <div class="panel-header">
                    <h2>${t.title}</h2>
                    <button class="close-button" id="closeReportBugModal">&times;</button>
                </div>
                <div class="panel-content">
                    <form id="reportBugForm" enctype="multipart/form-data" class="form-section">
                        <input type="hidden" name="create_user_issue" value="1">
                        <div class="username-section" id="usernameContainer"></div>
                        <input type="hidden" name="username" id="usernameField">
                        <select id="issueTypeField" name="issue_type" required>
                            <option value="" disabled selected>${t.issueTypeLabel}</option>
                            ${t.issueTypes.map(o => `<option value="${o.value}">${o.text}</option>`).join('')}
                        </select>
                        <input type="text" id="problemField" name="problem" required placeholder="${t.problemLabel}">
                        <textarea id="descriptionField" name="description" rows="4" placeholder="${t.descriptionLabel}"></textarea>
                        <div class="attachments-container" id="dropZone">
                            <p>${t.dropZoneText}</p>
                            <input type="file" id="fileInput" multiple style="display:none" accept="image/*" name="fileInput[]">
                        </div>
                        <div class="extra-action-buttons">
                            <button type="button" class="screen-record-btn" id="screenRecordingBtn">${t.screenRecording}</button>
                            <button type="button" class="screen-capture-btn" id="screenCapturingBtn">${t.screenCapturing}</button>
                        </div>
                        <div class="my-issues-btn-container">
                            <button type="button" class="my-issues-btn" id="myIssuesBtn">${t.myIssuesBtnShow}</button>
                        </div>
                        <button type="submit" class="submit-btn">${t.submitBtn}</button>
                        <div class="message-box">
                            <div class="success-message" id="successMessage">${t.successMessage}</div>
                            <div class="error-message" id="errorMessage"></div>
                        </div>
                    </form>
                    <div class="my-issues-container" id="myIssuesContainer">
                        <div class="my-issues-tabs-container">
                            <ul class="my-issues-tabs">
                                <li class="active" data-tab="notDoneTab">${t.myIssuesNotDoneTab}</li>
                                <li data-tab="doneTab">${t.myIssuesDoneTab}</li>
                            </ul>
                        </div>
                        <div class="my-issues-content">
                            <div class="my-issues-tab-pane active" id="notDoneTab"></div>
                            <div class="my-issues-tab-pane" id="doneTab"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right-click context menu -->
            <div class="custom-context-menu" id="customContextMenu">
                <div class="context-menu-item" id="pasteImageOption">${t.pasteImage}</div>
            </div>
        </div>

        <!-- Image Overlay (for enlarged images/videos) -->
        <div id="imageOverlay" class="image-overlay">
            <div class="image-overlay-content" id="imageOverlayContent">
                <span class="close-overlay" id="closeOverlayBtn">&times;</span>
                <img id="imageOverlayImg" src="" alt="Full Image">
            </div>
        </div>

        <!-- Added Modal for User Comments -->
        <div class="modal" id="commentsModal">
          <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">הערות</h5>
                <button type="button" class="btn-close close-modal-btn"></button>
              </div>
              <div class="modal-body" id="commentsModalBody"></div>
            </div>
          </div>
        </div>
    `;
    document.body.appendChild(container);

    // Detect user
    const userNameEl = document.querySelector('.user.logged_in .user-name .name');
    const userRankEl = document.querySelector('.user.logged_in .user-name .rank');
    const usernameContainer = container.querySelector('#usernameContainer');
    const usernameField = container.querySelector('#usernameField');
    if (userNameEl && userRankEl && usernameContainer && usernameField) {
        let fullName = userNameEl.textContent.trim() + " - " + userRankEl.textContent.trim();
        usernameField.value = fullName;
        usernameContainer.textContent = fullName;
        console.log("Detected logged in user:", fullName);
    } else {
        console.log("No .user.logged_in .user-name .name/.rank found. Username will be empty if not set manually.");
    }

    // Refs
    const reportBtn = container.querySelector('#reportBugBtn');
    const reporterPanel = container.querySelector('#reporterPanel');
    const closeModalBtn = container.querySelector('#closeReportBugModal');
    const reportForm = container.querySelector('#reportBugForm');
    const dropZone = container.querySelector('#dropZone');
    const fileInput = container.querySelector('#fileInput');
    const successMessage = container.querySelector('#successMessage');
    const errorMessage = container.querySelector('#errorMessage');
    const screenRecordingBtn = container.querySelector('#screenRecordingBtn');
    const screenCapturingBtn = container.querySelector('#screenCapturingBtn');
    const myIssuesBtn = container.querySelector('#myIssuesBtn');
    const myIssuesContainer = container.querySelector('#myIssuesContainer');
    const notDoneTab = container.querySelector('#notDoneTab');
    const doneTab = container.querySelector('#doneTab');
    const customContextMenu = container.querySelector('#customContextMenu');
    const pasteImageOption = container.querySelector('#pasteImageOption');

    // Image overlay references
    const imageOverlay = document.getElementById('imageOverlay');
    const imageOverlayImg = document.getElementById('imageOverlayImg');
    const closeOverlayBtn = document.getElementById('closeOverlayBtn');

    let filesToUpload = [];
    let existingImages = [];
    let imagesToRemove = [];
    let myIssuesOpen = false;
    let isEditing = false;
    let currentEditIssueId = null;
    let currentReopen = false;

    resetMessages();
    showPanel(false);
    initTabs();

    // Event handlers
    reportBtn.addEventListener('click', togglePanel);
    closeModalBtn.addEventListener('click', closePanel);
    reportForm.addEventListener('submit', onFormSubmit);

    dropZone.addEventListener('click', (e) => {
        if (e.target === dropZone || e.target.tagName.toLowerCase() === 'p') {
            fileInput.click();
        }
    });
    fileInput.addEventListener('change', handleFileSelect);
    dropZone.addEventListener('dragover', handleDragOver);
    dropZone.addEventListener('dragleave', handleDragLeave);
    dropZone.addEventListener('drop', handleFileDrop);

    document.addEventListener('paste', handlePaste);
    dropZone.addEventListener('contextmenu', e => {
        e.preventDefault();
        showContextMenu(e.pageX, e.pageY);
    });
    document.addEventListener('click', () => hideContextMenu());
    pasteImageOption.addEventListener('click', async () => {
        hideContextMenu();
        await tryPasteImageFromClipboard();
    });

    screenRecordingBtn.addEventListener('click', () => {
        console.log("Screen recording button clicked");
        closePanel();
        if (window.recordModule && typeof window.recordModule.startScreenRecording === 'function') {
            window.recordModule.startScreenRecording();
        }
    });
    screenCapturingBtn.addEventListener('click', () => {
        console.log("Screen capturing button clicked");
        closePanel();
        if (window.captureModule && typeof window.captureModule.startScreenCapture === 'function') {
            window.captureModule.startScreenCapture();
        }
    });

    // Listen for a saved recording or capture
    document.addEventListener('screenRecordingSaved', (e) => {
        console.log("Received screenRecordingSaved:", e.detail);
        if (e.detail && e.detail.videoFile) {
            addFiles([e.detail.videoFile]);
        }
        showPanel(true);
    });
    document.addEventListener('screenCaptureSaved', (e) => {
        console.log("Received screenCaptureSaved:", e.detail);
        if (e.detail && e.detail.imageFile) {
            addFiles([e.detail.imageFile]);
        }
        showPanel(true);
    });

    myIssuesBtn.addEventListener('click', () => {
        if (!myIssuesOpen) loadMyIssues();
        toggleMyIssues();
    });

    closeOverlayBtn.addEventListener('click', () => {
        closeImageOverlay();
    });
    imageOverlay.addEventListener('click', (e) => {
        if (e.target === imageOverlay) {
            closeImageOverlay();
        }
    });

    // -------------------------------------------
    // Functions
    // -------------------------------------------
    function initTabs() {
        const tabs = myIssuesContainer.querySelectorAll('.my-issues-tabs li');
        const panes = myIssuesContainer.querySelectorAll('.my-issues-tab-pane');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(x => x.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                const tg = tab.getAttribute('data-tab');
                myIssuesContainer.querySelector('#' + tg).classList.add('active');
            });
        });
    }

    function showPanel(show) {
        if (show) {
            reporterPanel.classList.add('open');
            reporterPanel.style.display = 'flex';
        } else {
            reporterPanel.classList.remove('open');
            reporterPanel.style.display = 'none';
        }
    }

    function togglePanel() {
        const isOpen = reporterPanel.classList.contains('open');
        if (!isOpen) {
            resetMessages();
            clearForm();
        }
        showPanel(!isOpen);
    }

    function closePanel() {
        showPanel(false);
        resetMessages();
        clearForm();
    }

    function toggleMyIssues() {
        myIssuesOpen = !myIssuesOpen;
        const pc = reporterPanel.querySelector('.panel-content');
        if (myIssuesOpen) {
            pc.classList.add('show-panel-content');
            reporterPanel.classList.add('my-issues-open');
            myIssuesBtn.textContent = t.myIssuesBtnHide;
            loadMyIssues();
        } else {
            pc.classList.remove('show-panel-content');
            reporterPanel.classList.remove('my-issues-open');
            myIssuesBtn.textContent = t.myIssuesBtnShow;
        }
    }

    function handleFileSelect() {
        addFiles(fileInput.files);
        fileInput.value = "";
    }

    function handleDragOver(e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    }

    function handleDragLeave() {
        dropZone.classList.remove('drag-over');
    }

    function handleFileDrop(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        addFiles(e.dataTransfer.files);
    }

    function handlePaste(e) {
        if (reporterPanel.classList.contains('open')) {
            if (e.clipboardData && e.clipboardData.items) {
                let found = false;
                for (let i = 0; i < e.clipboardData.items.length; i++) {
                    const it = e.clipboardData.items[i];
                    if (it.type.indexOf("image") !== -1) {
                        const file = it.getAsFile();
                        if (file) {
                            addFiles([file]);
                            found = true;
                        }
                    }
                }
                if (found) {
                    e.preventDefault();
                }
            }
        }
    }

    async function tryPasteImageFromClipboard() {
        if (navigator.clipboard && navigator.clipboard.read) {
            try {
                const items = await navigator.clipboard.read();
                let imageFound = false;
                for (const item of items) {
                    for (const type of item.types) {
                        if (type.startsWith('image/')) {
                            const blob = await item.getType(type);
                            if (blob) {
                                addFiles([blob]);
                                imageFound = true;
                            }
                        }
                    }
                }
                if (!imageFound) {
                    alert("לא נמצאה תמונה בלוח הגזירים");
                }
            } catch (err) {
                alert("לא ניתן לקרוא מהלוח:" + err);
            }
        } else {
            alert("דפדפן לא תומך בהדבקת תמונות");
        }
    }

    function showContextMenu(x, y) {
        customContextMenu.style.left = x + 'px';
        customContextMenu.style.top = y + 'px';
        customContextMenu.style.display = 'block';
    }

    function hideContextMenu() {
        customContextMenu.style.display = 'none';
    }

    function addFiles(fileList) {
        // Accept images or videos
        const validFiles = Array.from(fileList).filter(
            f => f.type.startsWith('image/') || f.type.startsWith('video/')
        );
        const spaceLeft = MAX_IMAGES - (filesToUpload.length + existingImages.length - imagesToRemove.length);
        const totalAfter = filesToUpload.length + existingImages.length - imagesToRemove.length + validFiles.length;

        if (totalAfter > MAX_IMAGES) {
            const toAdd = validFiles.slice(0, spaceLeft);
            if (toAdd.length > 0) filesToUpload.push(...toAdd);
            showErrorMessage(t.maxImagesError);
        } else {
            filesToUpload.push(...validFiles);
        }
        updateImagePreviews();
    }

    function updateImagePreviews() {
        dropZone.querySelectorAll('.image-wrapper').forEach(x => x.remove());

        // Existing images
        existingImages.forEach((imgName) => {
            if (imagesToRemove.includes(imgName)) return;
            const wrap = document.createElement('div');
            wrap.classList.add('image-wrapper');
            const extension = (imgName.split('.').pop() || '').toLowerCase();
            const videoExtensions = ['mp4','webm','ogg','mov','avi','mkv'];
            const isVideo = videoExtensions.includes(extension);
            const src = `issue-reporter/issues-uploads/${imgName}`;

            if (isVideo) {
                const videoDiv = document.createElement('div');
                videoDiv.style.display = 'flex';
                videoDiv.style.alignItems = 'center';
                videoDiv.style.justifyContent = 'center';
                videoDiv.style.width = '100%';
                videoDiv.style.height = '100%';
                videoDiv.style.background = '#000';

                const playBtn = document.createElement('div');
                playBtn.textContent = '►';
                playBtn.style.fontSize = '24px';
                playBtn.style.color = '#fff';
                playBtn.style.cursor = 'pointer';
                playBtn.addEventListener('click', () => openImageOverlay(src));

                videoDiv.appendChild(playBtn);
                wrap.appendChild(videoDiv);
            } else {
                const imgEl = document.createElement('img');
                imgEl.src = src;
                imgEl.classList.add('preview-img');
                imgEl.addEventListener('click', () => openImageOverlay(src));
                wrap.appendChild(imgEl);
            }

            const rmBtn = document.createElement('button');
            rmBtn.textContent = '×';
            rmBtn.title = t.removeBtn;
            rmBtn.classList.add('remove-image-btn');
            rmBtn.addEventListener('click', (evt) => {
                evt.stopPropagation();
                imagesToRemove.push(imgName);
                updateImagePreviews();
            });
            wrap.appendChild(rmBtn);

            dropZone.appendChild(wrap);
        });

        // Newly selected files
        filesToUpload.forEach((file, idx) => {
            const wrap = document.createElement('div');
            wrap.classList.add('image-wrapper');
            if (file.type.startsWith('video/')) {
                const videoDiv = document.createElement('div');
                videoDiv.style.display = 'flex';
                videoDiv.style.alignItems = 'center';
                videoDiv.style.justifyContent = 'center';
                videoDiv.style.width = '100%';
                videoDiv.style.height = '100%';
                videoDiv.style.background = '#000';

                const playBtn = document.createElement('div');
                playBtn.textContent = '►';
                playBtn.style.fontSize = '24px';
                playBtn.style.color = '#fff';
                playBtn.style.cursor = 'pointer';
                playBtn.addEventListener('click', () => openImageOverlay(URL.createObjectURL(file)));

                videoDiv.appendChild(playBtn);
                wrap.appendChild(videoDiv);
            } else {
                const imgEl = document.createElement('img');
                imgEl.src = URL.createObjectURL(file);
                imgEl.classList.add('preview-img');
                imgEl.addEventListener('click', () => openImageOverlay(imgEl.src));
                wrap.appendChild(imgEl);
            }

            const rmBtn = document.createElement('button');
            rmBtn.textContent = '×';
            rmBtn.title = t.removeBtn;
            rmBtn.classList.add('remove-image-btn');
            rmBtn.addEventListener('click', (evt) => {
                evt.stopPropagation();
                filesToUpload.splice(idx, 1);
                updateImagePreviews();
            });
            wrap.appendChild(rmBtn);

            dropZone.appendChild(wrap);
        });
    }

    function openImageOverlay(src) {
        imageOverlayImg.src = src;
        imageOverlay.classList.add('open');
    }

    function closeImageOverlay() {
        imageOverlay.classList.remove('open');
        imageOverlayImg.src = '';
    }

    function onFormSubmit(e) {
        e.preventDefault();
        const fd = new FormData(reportForm);
        filesToUpload.forEach(f => fd.append('fileInput[]', f));
        imagesToRemove.forEach(rm => fd.append('removeImages[]', rm));

        const un = usernameField.value.trim();
        if (!un) {
            showErrorMessage("Username missing");
            return;
        }
        fd.set('username', un);
        fd.set('force_json', '1');

        if (!isEditing) {
            // Create new
            fetch('issue-reporter/issue-reporter-user.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showSuccessMessage();
                    } else {
                        showErrorMessage(res.error || "Unknown error");
                    }
                })
                .catch(err => {
                    showErrorMessage(err.message || "Unknown error");
                });
        } else {
            // Edit existing
            fd.delete('create_user_issue');
            fd.set('user_action', 'edit_user_issue');
            fd.set('issue_id', currentEditIssueId);
            if (currentReopen) fd.set('reopen', '1');

            fetch('issue-reporter/issue-reporter-user.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showSuccessMessage();
                    } else {
                        showErrorMessage(res.error || "Unknown error");
                    }
                })
                .catch(err => {
                    showErrorMessage(err.message || "Unknown error");
                });
        }
    }

    function showSuccessMessage() {
        successMessage.style.display = 'block';
        errorMessage.style.display = 'none';
        setTimeout(() => {
            successMessage.style.display = 'none';
            closePanel();
            loadMyIssues();
        }, 1500);
    }

    function showErrorMessage(msg) {
        successMessage.style.display = 'none';
        errorMessage.style.display = 'block';
        errorMessage.textContent = t.errorSubmitting + ' ' + msg;
    }

    function resetMessages() {
        successMessage.style.display = 'none';
        errorMessage.style.display = 'none';
        errorMessage.textContent = '';
    }

    function clearForm() {
        reportForm.reset();
        filesToUpload = [];
        existingImages = [];
        imagesToRemove = [];
        updateImagePreviews();
        isEditing = false;
        currentEditIssueId = null;
        currentReopen = false;
        container.querySelector('#issueTypeField').selectedIndex = 0;
        container.querySelector('#problemField').value = '';
        container.querySelector('#descriptionField').value = '';
    }

    function loadMyIssues() {
        const un = usernameField.value.trim();
        if (!un) return;
        fetch('issue-reporter/issue-reporter-user.php?json=1&username=' + encodeURIComponent(un))
            .then(r => r.json())
            .then(data => {
                renderMyIssues(data);
            })
            .catch(e => console.error("Error loadMyIssues:", e));
    }

    function renderMyIssues(issues) {
        const notDone = issues.filter(i => i.status !== 'done');
        const done = issues.filter(i => i.status === 'done');
        notDoneTab.innerHTML = renderIssuesTable(notDone, false);
        doneTab.innerHTML = renderIssuesTable(done, true);
        attachTableActions(notDoneTab, false);
        attachTableActions(doneTab, true);
    }

    function renderIssuesTable(list, isDone) {
        if (!list || !list.length) {
            return `<div class="no-issues-msg">${t.noIssues}</div>`;
        }
        let html = `
            <div class="issues-table-wrapper">
                <table class="issues-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>${t.issueType}</th>
                            <th>${t.titleCol}</th>
                            <th>${t.descriptionCol}</th>
                            <th>${t.images}</th>
                            <th>${t.status}</th>
                            <th>${t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        list.forEach(issue => {
            const typeLabel = (t.issueTypes.find(x => x.value === issue.issue_type)?.text) || issue.issue_type;
            const imagesHtml = (issue.images || []).map(img =>
                `<img src="issue-reporter/issues-uploads/${img}" class="issue-img-thumb" alt="Issue Image">`
            ).join('');
            html += `
                <tr data-issue-id="${issue.id}">
                    <td>${issue.id}</td>
                    <td>${typeLabel}</td>
                    <td>${issue.problem}</td>
                    <td>${issue.description || ''}</td>
                    <td><div class="images-container">${imagesHtml}</div></td>
                    <td>${issue.status}</td>
                    <td>
            `;
            if (isDone) {
                html += `<button class="btn btn-sm btn-info reopen-btn">${t.reopen}</button>`;
            } else {
                html += `
                    <button class="btn btn-sm btn-secondary edit-issue-btn">${t.editIssue}</button>
                    <button class="btn btn-sm btn-danger delete-issue-btn">${t.delete}</button>
                `;
            }
            html += `<button class="btn btn-sm btn-primary view-comments-user-btn">${t.comments}</button>`;
            html += `</td></tr>`;
        });
        html += `</tbody></table></div>`;
        return html;
    }

    function attachTableActions(tblContainer, isDone) {
        tblContainer.querySelectorAll('.issue-img-thumb').forEach(img => {
            img.addEventListener('click', () => {
                openImageOverlay(img.src);
            });
        });
        tblContainer.querySelectorAll('.view-comments-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tr = btn.closest('tr');
                const issueId = tr.dataset.issueId;
                console.log("View comments button clicked. IssueID=", issueId);
                const ev = new CustomEvent('openCommentsModal', {
                    detail: {
                        issueId: issueId,
                        username: document.getElementById('usernameField').value.trim()
                    }
                });
                document.dispatchEvent(ev);
            });
        });
        if (isDone) {
            tblContainer.querySelectorAll('.reopen-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('tr');
                    const issueId = row.dataset.issueId;
                    reopenIssue(issueId);
                });
            });
        } else {
            tblContainer.querySelectorAll('.edit-issue-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('tr');
                    startEditIssue(row);
                });
            });
            tblContainer.querySelectorAll('.delete-issue-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (!confirm(t.confirmDelete)) return;
                    const row = btn.closest('tr');
                    const issueId = row.dataset.issueId;
                    deleteIssue(issueId);
                });
            });
        }
    }

    function reopenIssue(issueId) {
        const fd = new FormData();
        fd.set('user_action', 'edit_user_issue');
        fd.set('issue_id', issueId);
        fd.set('username', document.getElementById('usernameField').value.trim());
        fd.set('reopen', '1');
        fd.set('force_json', '1');

        fetch('issue-reporter/issue-reporter-user.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(t.updateSuccess);
                    loadMyIssues();
                } else {
                    alert(res.error || "Unknown error");
                }
            })
            .catch(err => {
                alert(err.message || "Unknown error");
            });
    }

    function startEditIssue(rowEl) {
        const issueId = rowEl.dataset.issueId;
        const tds = rowEl.querySelectorAll('td');
        const issueTypeText = tds[1].textContent.trim();
        const problem = tds[2].textContent.trim();
        const desc = tds[3].textContent.trim();
        let foundVal = "";
        for (const x of t.issueTypes) {
            if (x.text === issueTypeText) {
                foundVal = x.value;
                break;
            }
        }
        isEditing = true;
        currentEditIssueId = issueId;
        reportForm.reset();
        filesToUpload = [];
        existingImages = [];
        imagesToRemove = [];
        updateImagePreviews();

        container.querySelector('#issueTypeField').value = foundVal || "";
        container.querySelector('#problemField').value = problem;
        container.querySelector('#descriptionField').value = desc;

        // Gather existing images
        const imgEls = rowEl.querySelectorAll('.images-container img.issue-img-thumb');
        imgEls.forEach(img => {
            const src = img.getAttribute('src') || '';
            const fileName = src.split('/').pop();
            if (fileName) {
                existingImages.push(fileName);
            }
        });
        updateImagePreviews();

        if (!reporterPanel.classList.contains('open')) {
            togglePanel();
        }
    }

    function deleteIssue(issueId) {
        const fd = new FormData();
        fd.set('user_action', 'delete_issue_user');
        fd.set('issue_id', issueId);
        fd.set('username', document.getElementById('usernameField').value.trim());
        fd.set('force_json', '1');

        fetch('issue-reporter/issue-reporter-user.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(t.updateSuccess);
                    loadMyIssues();
                } else {
                    alert(res.error || "Unknown error");
                }
            })
            .catch(err => {
                alert(err.message || "Unknown error");
            });
    }

    // Polling to detect changes in issues.json
    let lastIssuesData = null;
    function pollIssuesFile() {
        const username = document.getElementById('usernameField').value.trim();
        if (!username) return;
        fetch('issue-reporter/issue-reporter-user.php?json=1&username=' + encodeURIComponent(username))
            .then(r => r.json())
            .then(data => {
                if (!data) return;
                const dataString = JSON.stringify(data);
                if (lastIssuesData && dataString !== lastIssuesData) {
                    loadMyIssues();
                }
                lastIssuesData = dataString;
            })
            .catch(err => console.log('Polling error', err));
    }
    setInterval(pollIssuesFile, 5000);
    pollIssuesFile();
});

