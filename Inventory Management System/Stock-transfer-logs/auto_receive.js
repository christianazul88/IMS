/**
 * Auto-receive flow for stock transfers pending > 3 days.
 * 1. GET get_pending_transfers.php -> list of pending transfer ids
 * 2. If more than 1, show a modal listing them
 * 3. POST/GET test.php -> runs the auto-receive, returns JSON counts
 * 4. Show success count vs pending count
 *
 * Uses a plain-HTML modal so it has no dependency on Bootstrap/jQuery.
 * Swap the buildModal()/showModal() bodies for your framework's modal
 * if you already have one (e.g. Bootstrap's $('#myModal').modal('show')).
 */

async function runAutoReceiveCheck() {
    const pendingRes = await fetch('get_pending_transfers.php');
    const pending = await pendingRes.json();

    if (pending.count > 1) {
        showPendingModal(pending.ids, async () => {
            await runAutoReceive(pending.count);
        });
    }
}

function showPendingModal(ids, onConfirm) {
    const list = ids.map(id => `transfer #: ${id}`).join('<br>');

    const overlay = document.createElement('div');
    overlay.id = 'autoReceiveModalOverlay';
    overlay.style.cssText = `
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        display: flex; align-items: center; justify-content: center; z-index: 9999;
    `;

    overlay.innerHTML = `
        <div style="background:#fff; padding:24px; border-radius:8px; max-width:420px; width:90%;">
            <h3 style="margin-top:0;">Auto receiving transfers</h3>
            <p>Auto receiving transfer that were more than 3 days not being received:</p>
            <div style="max-height:200px; overflow-y:auto; margin-bottom:16px;">${list}</div>
            <button id="autoReceiveConfirmBtn" style="padding:8px 16px;">OK</button>
        </div>
    `;

    document.body.appendChild(overlay);

    document.getElementById('autoReceiveConfirmBtn').addEventListener('click', () => {
        overlay.remove();
        onConfirm();
    });
}

async function runAutoReceive(pendingCount) {
    const res = await fetch('test.php');
    const data = await res.json();

    // data.total should equal pendingCount (same WHERE clause), and
    // data.success tells you how many actually completed cleanly.
    if (data.success === pendingCount) {
        alert(`All ${data.success} pending transfers were auto-received successfully.`);
    } else {
        const failed = data.results.filter(r => !r.success);
        console.warn('Some transfers failed to auto-receive:', failed);
        alert(`${data.success} of ${pendingCount} pending transfers were auto-received. Check console for details on the rest.`);
    }
}

// Trigger on page load, or hook this up to a button/cron-style AJAX poll instead.
document.addEventListener('DOMContentLoaded', runAutoReceiveCheck);
