# Dennice invite

Run through XAMPP/Apache, not by opening the HTML file directly:

- Invite: http://localhost/princess_protocol/date-invite/
- Activity: http://localhost/princess_protocol/date-invite/admin.php

The local PHP preview started for this task uses `http://127.0.0.1:8234/princess_protocol/date-invite/` (append `admin.php` for the dashboard). It runs on this computer only. XAMPP/Apache can serve the same files using the URLs above when Apache is started.

The dashboard only allows connections from the server computer (127.0.0.1 or ::1). It shows browser-level visits, selected/removed choices, confirmation, hold starts/cancellations/resets, screens, mascot touches, and link/button clicks. Refresh to see new events; filter by browser to follow one history.

## Activity storage

The JSON file is `C:/xampp/private/date-invite/activity.json`, outside `htdocs`. PHP creates it on first use, with file locking for simultaneous requests. The page briefly discloses recording. No typed names, IP addresses, fingerprinting, or raw keystrokes are saved.

One visit means one page load; duplicate visit requests are deduplicated. Reopening or refreshing counts as another visit. A random browser ID in localStorage connects repeat visits. Another device, private browsing, or cleared storage can look like a new browser. The dashboard cannot verify the person's identity. Failed tracking does not block the invite and is not retried automatically; network failures can mean missed events.

When moving to a different host, configure `INVITE_DATA_FILE` with a writable absolute file path outside the public document root. Do not expose the dashboard through a public proxy/tunnel that turns remote requests into loopback requests. Remote dashboard access is deliberately disabled; use server-local access or add authenticated hosting before making it public.

## Music

Place your MP3 in `date-invite/assets/music.mp3`. The Music toggle appears automatically after a page refresh when the file exists. This is where your Purple Rain × Kiss It Better file goes. No track is bundled or downloaded. Playback starts only after tapping Music, loops quietly, and pauses when the page is hidden. Sound effects use synthesized tones; the separate Sound toggle controls them.

## Interaction details

- Green hold: approach, nudge, reset ring, then a fresh 3-second hold to accept. Releasing anytime cancels.
- Red hold: pause, approach, short pull, stop and speak, continue. The ring resets when the button moves; it does not imply an answer was submitted.
- After red leaves: the green presentation follows. After 12 seconds without a hold, both choices return.
- Choosing red again after its return completes a real 3-second pass hold and opens the red finale. The prank only runs once per page load.
- “Ibang araw na lang” always provides a straightforward way to decline.
- The name animation leads straight to the invitation. A completed green hold goes directly to the finale with five locally drawn, animated cats; the human caricature is hidden during this celebration. There is no activity picker or extra greeting screen.
- Repeat greetings vary for visit 2, visit 3, and later visits.

## Checks

PHP files can be syntax-checked using `php -l`; `experience.js` and `cats.js` with `node --check`. Run `node date-invite/test-finale.cjs` from the project folder for flow regression checks. For manual tests, cover canceled green/red holds, completed green after its ring reset, the cat celebration, decline, repeat loads, Sound, and the private dashboard. Use a separate `INVITE_DATA_FILE` for test traffic to keep the real history clean.
