# genai/google-form

Submit data to a Google Form **from the server** — no front-end JS, no Apps Script.
POSTs to the form's `formResponse` endpoint with your fields mapped to `entry.NNN` ids.

## Use
```php
$form = new GenAI\GoogleForm\GoogleForm($formId, array(
    'name'  => 'entry.111111111',
    'email' => 'entry.222222222',
));
$ok = $form->submit(array('name' => 'Linh', 'email' => 'linh@example.com'));
```
`submitRaw(array('entry.111111111' => 'Linh'))` bypasses the map. Array values
(checkboxes) are sent as repeated keys.

## Get the ids
- **formId**: the `.../forms/d/e/<formId>/viewform` segment.
- **entry ids**: Form → ⋮ → *Get pre-filled link* → fill samples → copy; the URL
  has `entry.NNN=...` per field.

## Config + wiring
```ini
[googleform]
form_id = "1FAIpQLSf..."
```
```php
#[Configuration]
class GoogleFormConfig {
    #[Bean(\GenAI\GoogleForm\GoogleForm::class)]
    public function googleForm(\GenAI\GoogleForm\Bundle\GoogleFormProperty $cfg) {
        return new \GenAI\GoogleForm\GoogleForm($cfg->getFormId(), array(
            'name' => 'entry.111111111', 'email' => 'entry.222222222',
        ));
    }
}
```
`GoogleFormProperty` auto-registers (the package declares `extra.genai.scan`); the
field map is form-specific, so it lives in the app's bean.

## Behaviour & caveats
- **Best-effort:** short timeout (default 5s), and it returns `false` (never
  throws) on a network error or an unset `form_id` — so a failed submit won't
  break the action that called it (e.g. registration still succeeds).
- **Success isn't strictly verifiable:** Google returns 200 even when it silently
  rejects a submission (missing required field), so `true` means "sent". Map every
  required field and confirm once in the form's Responses tab.
- The form must be **public** (no login / not "limit to 1 response").
- It blocks for up to the timeout; lower it, or move the call to a shutdown hook,
  if you don't want registration to wait.
