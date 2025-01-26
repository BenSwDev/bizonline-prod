// issue-reporter-comments-modal.js
document.addEventListener('DOMContentLoaded', function () {
    console.log("issue-reporter-comments-modal.js loaded.");

    let cmEl = document.getElementById('commentsModal');
    let cmBody = document.getElementById('commentsModalBody');

    let msgDiv = document.createElement('div');
    msgDiv.id = 'commentsModalMsg';
    msgDiv.style.marginBottom = '10px';
    cmBody.appendChild(msgDiv);

    function showModal() {
        cmEl.style.display = 'flex';
        cmEl.classList.add('show');
    }
    function hideModal() {
        cmEl.style.display = 'none';
        cmEl.classList.remove('show');
    }

    function showModalMsg(msg, isErr = false) {
        msgDiv.className = '';
        if (!msg) {
            msgDiv.textContent = '';
            return;
        }
        msgDiv.classList.add(isErr ? 'error-message' : 'success-message');
        msgDiv.textContent = msg;
        console.log("commentsModal:", isErr ? "Error:" : "Success:", msg);
    }

    function clearCommentsModal() {
        Array.from(cmBody.querySelectorAll('.comments, .add-comment-form')).forEach(x => x.remove());
        showModalMsg('', false);
    }

    // Close when clicking outside
    cmEl.addEventListener('click', (e) => {
        if (e.target === cmEl) hideModal();
    });

    // ----------------------------------------------------------------
    // Main event: open the modal
    // ----------------------------------------------------------------
    document.addEventListener('openCommentsModal', (evt) => {
        const { issueId, comments, isAdmin, username } = evt.detail;
        clearCommentsModal();
        if (!issueId) {
            showModalMsg('Missing issueId', true);
            showModal();
            return;
        }
        if (isAdmin) {
            // Admin already has comments from data- attribute
            if (!comments) {
                showModalMsg('Missing comment list for admin.', true);
                showModal();
                return;
            }
            const arr = JSON.parse(comments);
            openCommentsAdmin(issueId, arr);
        } else {
            // Regular user => we fetch from user side
            if (!username) {
                showModalMsg('Missing issueId or username.', true);
                showModal();
                return;
            }
            loadIssueUser(issueId, username);
        }
        showModal();
    });

    // ==================================================
    // ===============  ADMIN CONTEXT  ==================
    // ==================================================
    function openCommentsAdmin(issueId, commentsArray) {
        renderCommentsBlock(commentsArray, true, issueId, null);

        // Add form
        const addWrap = document.createElement('div');
        addWrap.classList.add('add-comment-form');
        const ta = document.createElement('textarea');
        ta.classList.add('form-control');
        ta.placeholder = 'הוסף הערה';
        ta.rows = 2;

        const btn = document.createElement('button');
        btn.classList.add('btn','btn-success','btn-sm');
        btn.textContent = 'הוסף';
        btn.style.marginTop = '0.5rem';
        btn.addEventListener('click', () => {
            const v = ta.value.trim();
            if(!v) {
                showModalMsg('לא ניתן להוסיף הערה ריקה', true);
                return;
            }
            doAddCommentAdmin(issueId, v);
        });

        addWrap.appendChild(ta);
        addWrap.appendChild(btn);
        cmBody.appendChild(addWrap);
    }

    function reloadAdminComments(issueId) {
        fetch('issue-reporter.php?json=1')
          .then(r => r.json())
          .then(data => {
             const found = data.find(x => x.id == issueId);
             if(!found){
               showModalMsg('Issue not found after reload', true);
               return;
             }
             clearCommentsModal();
             openCommentsAdmin(issueId, found.comments || []);
             showModal();
          })
          .catch(err => showModalMsg(err.message, true));
    }

    function doAddCommentAdmin(issueId, text){
        showModalMsg('');
        const fd = new FormData();
        fd.set('add_comment','1');
        fd.set('issue_id', issueId);
        fd.set('comment', text);

        fetch('issue-reporter.php',{method:'POST',body:fd})
         .then(r=>r.text())
         .then(res=>{
            if(res.includes('comment_added_ok')){
               showModalMsg('ההערה נוספה בהצלחה');
               reloadAdminComments(issueId);
            } else {
               showModalMsg(res, true);
            }
         })
         .catch(err=>showModalMsg(err.message,true));
    }

    function doEditCommentAdmin(issueId, idx, newText){
        showModalMsg('');
        const fd=new FormData();
        fd.set('edit_comment','1');
        fd.set('issue_id', issueId);
        fd.set('comment_index', idx);
        fd.set('comment_text', newText);

        fetch('issue-reporter.php',{method:'POST',body:fd})
         .then(r=>r.text())
         .then(res=>{
           if(res.includes('comment_edited_ok')){
             showModalMsg('ההערה נערכה בהצלחה');
             reloadAdminComments(issueId);
           } else {
             showModalMsg(res, true);
           }
         })
         .catch(err=>showModalMsg(err.message,true));
    }

    function doDeleteCommentAdmin(issueId, idx){
        showModalMsg('');
        const fd=new FormData();
        fd.set('delete_comment','1');
        fd.set('issue_id', issueId);
        fd.set('comment_index', idx);

        fetch('issue-reporter.php',{method:'POST',body:fd})
         .then(r=>r.text())
         .then(res=>{
           if(res.includes('comment_deleted_ok')){
             showModalMsg('ההערה נמחקה בהצלחה');
             reloadAdminComments(issueId);
           } else {
             showModalMsg(res, true);
           }
         })
         .catch(err=>showModalMsg(err.message,true));
    }

    // ==================================================
    // ===============  USER CONTEXT  ===================
    // ==================================================
    function loadIssueUser(issueId, username){
        showModalMsg('');
        fetch('issue-reporter/issue-reporter-user.php?json=1&username='+encodeURIComponent(username))
         .then(r=>r.json())
         .then(arr=>{
           const found=arr.find(x=>x.id==issueId);
           if(!found){
              showModalMsg('Issue not found',true);
              return;
           }
           renderCommentsUser(found, username);
         })
         .catch(err=>showModalMsg(err.message,true));
    }

    function renderCommentsUser(issue, username){
        renderCommentsBlock(issue.comments||[], false, issue.id, issue);

        // If not done, allow "add comment"
        if(issue.status!=='done' && issue.username===username){
            const addWrap=document.createElement('div');
            addWrap.classList.add('add-comment-form');
            const ta=document.createElement('textarea');
            ta.classList.add('form-control');
            ta.placeholder='הוסף הערה';
            ta.rows=2;
            const btn=document.createElement('button');
            btn.classList.add('btn','btn-success','btn-sm');
            btn.textContent='הוסף';
            btn.style.marginTop='0.5rem';
            btn.addEventListener('click',()=>{
              const v=ta.value.trim();
              if(!v){
                showModalMsg('לא ניתן להוסיף הערה ריקה', true);
                return;
              }
              doAddCommentUser(issue.id, v, username);
            });
            addWrap.appendChild(ta);
            addWrap.appendChild(btn);
            cmBody.appendChild(addWrap);
        }
    }

    function reloadUserComments(issueId, username){
        clearCommentsModal();
        loadIssueUser(issueId, username);
    }

    function doAddCommentUser(issueId, text, username){
        showModalMsg('');
        const fd=new FormData();
        fd.set('add_comment','1');
        fd.set('issue_id', issueId);
        fd.set('comment', text);
        fd.set('username', username);
        fd.set('force_json','1');

        fetch('issue-reporter/issue-reporter-user.php',{method:'POST',body:fd})
         .then(r=>r.json())
         .then(js=>{
           if(js.success){
             showModalMsg('ההערה נוספה בהצלחה');
             reloadUserComments(issueId, username);
           } else {
             showModalMsg(js.error||'Error',true);
           }
         })
         .catch(err=> showModalMsg(err.message,true));
    }

    function doEditCommentUser(issueId, idx, newText, username){
        showModalMsg('');
        const fd=new FormData();
        fd.set('edit_comment','1');
        fd.set('issue_id', issueId);
        fd.set('comment_index', idx);
        fd.set('comment_text', newText);
        fd.set('username', username);
        fd.set('force_json','1');

        fetch('issue-reporter/issue-reporter-user.php',{method:'POST',body:fd})
         .then(r=>r.json())
         .then(js=>{
           if(js.success){
             showModalMsg('ההערה נערכה בהצלחה');
             reloadUserComments(issueId, username);
           } else {
             showModalMsg(js.error||'Unknown error',true);
           }
         })
         .catch(err=>showModalMsg(err.message,true));
    }

    function doDeleteCommentUser(issueId, idx, username){
        showModalMsg('');
        const fd=new FormData();
        fd.set('delete_comment','1');
        fd.set('issue_id', issueId);
        fd.set('comment_index', idx);
        fd.set('username', username);
        fd.set('force_json','1');

        fetch('issue-reporter/issue-reporter-user.php',{method:'POST',body:fd})
         .then(r=>r.json())
         .then(js=>{
           if(js.success){
             showModalMsg('ההערה נמחקה בהצלחה');
             reloadUserComments(issueId, username);
           } else {
             showModalMsg(js.error||'Unknown error',true);
           }
         })
         .catch(err=>showModalMsg(err.message,true));
    }

    // ==================================================
    // SHARED RENDERING
    // ==================================================
    function renderCommentsBlock(comments, isAdmin, issueId, userIssue){
        const cDiv=document.createElement('div');
        cDiv.classList.add('comments');
        if(!comments.length){
          const p=document.createElement('p');
          p.textContent='אין הערות';
          cDiv.appendChild(p);
        } else {
          comments.forEach((cmt, idx)=>{
             cDiv.appendChild( createCommentElement(cmt, idx, isAdmin, issueId, userIssue) );
          });
        }
        cmBody.appendChild(cDiv);
    }

    function createCommentElement(cmt, idx, isAdmin, issueId, userIssue){
        const box=document.createElement('div');
        box.classList.add('comment','p-2','border','rounded','mb-2');

        if(cmt.author && typeof cmt.author==='string'){
          if(cmt.author.toLowerCase()=== 'admin'){
            box.classList.add('admin-comment');
          }
        }

        const authorP=document.createElement('p');
        authorP.classList.add('comment-author');
        authorP.textContent=cmt.author;

        const timeP=document.createElement('p');
        timeP.classList.add('comment-time');
        timeP.textContent=cmt.timestamp;

        const textP=document.createElement('p');
        textP.classList.add('comment-text');
        textP.textContent=cmt.text;

        box.appendChild(authorP);
        box.appendChild(timeP);
        box.appendChild(textP);

        // Determine if user can modify
        let canModify=false;
        if(isAdmin){
          // We'll let server side enforce the admin matching
          if(cmt.author && cmt.author.toLowerCase()=== 'admin'){
            canModify=true;
          }
        } else if(userIssue){
          // The user can only edit if cmt.author===issue.username && status!='done'
          if(cmt.author===userIssue.username && userIssue.status!=='done'){
            canModify=true;
          }
        }

        if(canModify){
          const btnGroup=document.createElement('div');
          btnGroup.style.marginTop='5px';

          const editBtn=document.createElement('button');
          editBtn.classList.add('btn','btn-sm','btn-secondary');
          editBtn.style.marginRight='10px';
          editBtn.textContent='ערוך';

          let editMode=false;
          let editArea=null;

          editBtn.addEventListener('click', ()=>{
            if(!editMode){
              editMode=true;
              editBtn.textContent='שמור';
              editArea=document.createElement('textarea');
              editArea.classList.add('form-control');
              editArea.value=cmt.text;
              editArea.style.marginBottom='5px';

              box.insertBefore(editArea, textP);
              textP.style.display='none';
            } else {
              const newTx=editArea.value.trim();
              if(!newTx){
                showModalMsg('לא ניתן להשאיר הערה ריקה', true);
                return;
              }
              editMode=false;
              editBtn.textContent='ערוך';
              if(isAdmin){
                doEditCommentAdmin(issueId, idx, newTx);
              } else if(userIssue){
                doEditCommentUser(issueId, idx, newTx, userIssue.username);
              }
            }
          });

          const deleteBtn=document.createElement('button');
          deleteBtn.classList.add('btn','btn-sm','btn-danger');
          deleteBtn.textContent='מחק';
          deleteBtn.addEventListener('click',()=>{
            if(!confirm('מחק הערה זו?'))return;
            if(isAdmin){
              doDeleteCommentAdmin(issueId, idx);
            } else if(userIssue){
              doDeleteCommentUser(issueId, idx, userIssue.username);
            }
          });

          btnGroup.appendChild(editBtn);
          btnGroup.appendChild(deleteBtn);
          box.appendChild(btnGroup);
        }

        return box;
    }
});
