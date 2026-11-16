<?php /** @var array $tabs @var string $tab @var array $products @var ?array $gscStatus */
$providers = ai_providers();
?>
<div class="page-head">
    <div><h1>AI SEO agent</h1><p>Free AI writes the SEO of your cakes, and every change on the website is sent to Google and Bing by itself.</p></div>
</div>

<section class="card">
    <nav class="tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="<?= e(admin_url('ai-seo', ['tab' => $key])) ?>" class="<?= $tab === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

<?php if ($tab === 'ai'): ?>
    <div class="card__body form-stack">
        <p class="hint" style="margin:0">Add one or more free AI services. They are tried from the top: when one is busy, out of free use or down, the next one writes instead. Keys are saved encrypted and never shown again.</p>
        <?php if (!$providers): ?>
            <div class="note-box"><strong>No AI service yet.</strong> Start with <a href="https://console.groq.com/keys" target="_blank" rel="noopener">Groq</a> (fast, free) and add <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">Google Gemini</a> as a second one. Sign in, create an API key, copy it and paste it below.</div>
        <?php endif; ?>
        <div class="ai-services">
            <?php foreach ($providers as $i => $p): $last = $p['last_at'] ?? null; ?>
                <div class="ai-service <?= empty($p['enabled']) ? 'is-off' : '' ?>" data-ai-service="<?= e($p['id']) ?>">
                    <div class="ai-service__main">
                        <span class="ai-service__order"><?= $i + 1 ?></span>
                        <div class="ai-service__name">
                            <strong><?= e($p['name']) ?></strong>
                            <small><?= e($p['model'] ?: 'no model chosen') ?></small>
                        </div>
                        <div class="ai-service__state" data-ai-result>
                            <?php if (empty($p['enabled'])): ?><?= status_badge('inactive', 'Off') ?>
                            <?php elseif ($last === null): ?><?= status_badge('pending', 'Not tested') ?>
                            <?php else: ?><?= status_badge(!empty($p['last_ok']) ? 'active' : 'failed', !empty($p['last_ok']) ? 'Working' : 'Failed') ?><small><?= e(time_ago($last)) ?><?= empty($p['last_ok']) ? ': ' . e($p['last_message'] ?? '') : '' ?></small><?php endif; ?>
                        </div>
                    </div>
                    <div class="ai-service__actions">
                        <button type="button" class="btn btn--primary btn--sm" data-ai-test>Test</button>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>"><input type="hidden" name="action" value="toggle"><button class="btn btn--secondary btn--sm" type="submit"><?= empty($p['enabled']) ? 'Switch on' : 'Switch off' ?></button></form>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>"><input type="hidden" name="action" value="up"><button class="btn btn--ghost btn--sm" type="submit" aria-label="Move up" <?= $i === 0 ? 'disabled' : '' ?>>&uarr;</button></form>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>"><input type="hidden" name="action" value="down"><button class="btn btn--ghost btn--sm" type="submit" aria-label="Move down" <?= $i === count($providers) - 1 ? 'disabled' : '' ?>>&darr;</button></form>
                    </div>
                    <details class="ai-service__edit">
                        <summary>Change model or key</summary>
                        <form method="post" class="form-grid">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>"><input type="hidden" name="action" value="update_service">
                            <label class="field"><span>Model <small>press Test to load the list</small></span><input class="input" name="model" value="<?= e($p['model']) ?>" list="models-<?= e($p['id']) ?>" required><datalist id="models-<?= e($p['id']) ?>"></datalist></label>
                            <label class="field"><span>New API key <small>empty = keep the saved one</small></span><input class="input" type="password" name="api_key" autocomplete="off"></label>
                            <?php if ($p['service'] === 'custom'): ?><label class="field span-2"><span>Address</span><input class="input" name="base_url" value="<?= e($p['url']) ?>"></label><?php endif; ?>
                            <div class="span-2 ai-service__save"><button class="btn btn--primary btn--sm" type="submit">Save</button></div>
                        </form>
                        <form method="post" data-confirm="Remove <?= e($p['name']) ?> and its key?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>"><input type="hidden" name="action" value="remove"><button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', '', 14) ?> Remove</button></form>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card__body" style="border-top:1px solid var(--line)">
        <h2 class="card__subtitle">Add an AI service</h2>
        <form method="post" class="form-grid" data-ai-add>
            <?= csrf_field() ?><input type="hidden" name="action" value="add_service">
            <label class="field"><span>Service</span>
                <select class="input" name="service" data-ai-preset>
                    <?php foreach (AI_SERVICES as $key => $s): ?><option value="<?= e($key) ?>" data-model="<?= e($s['model']) ?>" data-url="<?= e($s['url']) ?>" data-keys="<?= e($s['keys']) ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="field"><span>API key <a href="https://console.groq.com/keys" target="_blank" rel="noopener" data-ai-keys-link>get a free key</a></span><input class="input" type="password" name="api_key" autocomplete="off" required></label>
            <label class="field"><span>Model <small>empty = the suggested one</small></span><input class="input" name="model" placeholder="<?= e(AI_SERVICES['groq']['model']) ?>" data-ai-model></label>
            <label class="field" data-ai-url hidden><span>Address <small>ends with /v1</small></span><input class="input" name="base_url" placeholder="https://api.example.com/v1"></label>
            <label class="field" data-ai-name hidden><span>Name</span><input class="input" name="name" maxlength="40" placeholder="e.g. My AI"></label>
            <div class="span-2"><button class="btn btn--primary" type="submit"><?= icon('plus', '', 16) ?> Add service</button></div>
        </form>
    </div>

<?php elseif ($tab === 'knowledge'): ?>
    <div class="card__body form-stack">
        <form method="post" class="form-stack">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_profile">
            <label class="field"><span>About your shop, for the AI <small>who you are, what you are known for, your style of writing, what it must always mention</small></span>
                <textarea class="input" name="ai_shop_profile" rows="10" maxlength="4000" data-ai-profile placeholder="e.g. Family-owned cake shop in Greenway since 2003. Known for fresh cream tortes and celebration cakes for Canberra's multicultural families..."><?= e(setting('ai_shop_profile')) ?></textarea></label>
            <div class="ai-writer__actions">
                <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> Save</button>
                <?php if (ai_ready()): ?><button class="btn btn--secondary" type="button" data-ai-draft><?= icon('spark', '', 16) ?> Write a draft from my website</button><?php endif; ?>
                <span class="ai-writer__status" data-ai-draft-status role="status"></span>
            </div>
        </form>
        <details class="ai-facts" open>
            <summary>What the AI also reads from the website, every time</summary>
            <p class="hint">Always today's details, so you never have to update them here. Change them where they live (Branches, Settings, Categories, FAQs).</p>
            <pre><?= e(ai_shop_facts()) ?></pre>
            <p class="hint" style="margin:0">It also gets the SEO rules, the cake's details, two of your own cake descriptions as a style example and the pages it may link to.</p>
        </details>
    </div>

<?php elseif ($tab === 'bulk'): ?>
    <div class="card__body form-stack">
        <?php if (!ai_ready()): ?>
            <div class="note-box">Add an AI service in <a href="<?= e(admin_url('ai-seo')) ?>">AI services</a> first.</div>
        <?php else: ?>
            <p class="hint" style="margin:0">Tick the cakes and press the button. Each cake gets a new keyword, title, search description, short and full description, search words and photo text, and is saved straight away. The old text of each cake is kept: open the cake and use <strong>Earlier versions &rarr; Restore</strong> if you prefer it.</p>
            <div class="ai-writer__actions">
                <button type="button" class="btn btn--secondary btn--sm" data-bulk-select="weak">Select cakes below 9/11</button>
                <button type="button" class="btn btn--secondary btn--sm" data-bulk-select="all">Select all</button>
                <button type="button" class="btn btn--ghost btn--sm" data-bulk-select="none">Clear</button>
                <button type="button" class="btn btn--primary btn--sm" data-bulk-run data-url="<?= e(admin_url('products/ai')) ?>"><?= icon('spark', '', 16) ?> Write with AI for selected</button>
                <span class="ai-writer__status" data-bulk-status role="status"></span>
            </div>
        <?php endif; ?>
    </div>
    <div class="table-wrap"><table class="table" data-bulk-table>
        <thead><tr><th style="width:40px"></th><th>Cake</th><th>Category</th><th>SEO</th><th>AI</th></tr></thead>
        <tbody>
            <?php foreach ($products as $p): [$sp, $st, $sl] = seo_score(product_seo_checks($p, (int) $p['photo_count'])); ?>
                <tr data-bulk-row="<?= (int) $p['id'] ?>" data-score="<?= $sp ?>">
                    <td><input type="checkbox" aria-label="Select <?= e($p['name']) ?>" <?= ai_ready() ? '' : 'disabled' ?>></td>
                    <td><a href="<?= e(admin_url('products/edit', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a><?= $p['is_active'] ? '' : ' ' . status_badge('inactive', 'Hidden') ?></td>
                    <td><?= e($p['category_name']) ?></td>
                    <td><span class="seo-score seo-score--<?= e($sl) ?>" data-bulk-score><?= $sp ?>/<?= $st ?></span></td>
                    <td data-bulk-result class="hint"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table></div>

<?php else: ?>
    <?php $gsc = gsc_account(); $log = json_decode(setting('search_engine_log', '[]'), true) ?: []; $public = site_is_public(); ?>
    <div class="card__body form-stack">
        <ul class="seo-checks">
            <li class="is-ok"><?= icon('check', '', 16) ?><span><strong>Sitemap updates itself</strong><small style="display:block">A new or changed cake is in <a href="<?= e(url('sitemap.xml')) ?>" target="_blank">sitemap.xml</a> straight away; a hidden or deleted one drops out.</small></span></li>
            <li class="<?= $public && setting('indexnow_enabled', '1') === '1' ? 'is-ok' : 'is-todo' ?>"><?= icon($public && setting('indexnow_enabled', '1') === '1' ? 'check' : 'info', '', 16) ?><span><strong>Bing, Yandex and ChatGPT search (IndexNow)</strong><small style="display:block"><?= $public ? 'Changed addresses are sent every 15 minutes by the cron job. ChatGPT search and Microsoft Copilot use Bing.' : 'Starts once the website runs on its https address.' ?></small></span></li>
            <li class="<?= $gsc ? 'is-ok' : 'is-todo' ?>"><?= icon($gsc ? 'check' : 'info', '', 16) ?><span><strong>Google Search Console</strong><small style="display:block"><?= $gsc ? 'Connected: the sitemap is submitted again after changes (at most once an hour).' : 'Not connected yet: follow the steps below once.' ?></small></span></li>
            <li class="is-ok"><?= icon('check', '', 16) ?><span><strong>Summary for AI assistants</strong><small style="display:block"><a href="<?= e(url('llms.txt')) ?>" target="_blank">llms.txt</a> lists the shop, pages and cakes for ChatGPT, Perplexity, Claude and Gemini. Built from the website, always up to date.</small></span></li>
        </ul>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="send_all">
            <button class="btn btn--primary" type="submit"><?= icon('upload', '', 16) ?> Send everything now</button>
            <span class="hint">Sends every address to Bing and the sitemap to Google, now.</span>
        </form>
    </div>

    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h2 class="card__subtitle">Google Search Console</h2>
        <?php if ($gsc): ?>
            <p style="margin:0">Connected as <code><?= e($gsc['client_email']) ?></code> to <strong><?= e(setting('gsc_property') ?: 'no property found yet') ?></strong>.</p>
            <?php if ($gscStatus): $contents = $gscStatus['contents'][0] ?? []; ?>
                <div class="stat-row">
                    <div><span>Last submitted</span><strong><?= !empty($gscStatus['lastSubmitted']) ? e(date('j M, g:ia', strtotime($gscStatus['lastSubmitted']))) : '-' ?></strong></div>
                    <div><span>Google last read it</span><strong><?= !empty($gscStatus['lastDownloaded']) ? e(date('j M, g:ia', strtotime($gscStatus['lastDownloaded']))) : (!empty($gscStatus['isPending']) ? 'Waiting' : '-') ?></strong></div>
                    <div><span>Addresses</span><strong><?= (int) ($contents['submitted'] ?? 0) ?></strong></div>
                    <div><span>Errors / warnings</span><strong><?= (int) ($gscStatus['errors'] ?? 0) ?> / <?= (int) ($gscStatus['warnings'] ?? 0) ?></strong></div>
                </div>
            <?php else: ?>
                <p class="hint" style="margin:0">Google has not reported on the sitemap yet. Press "Send everything now".</p>
            <?php endif; ?>
            <details>
                <summary class="hint">Change the property or disconnect</summary>
                <form method="post" class="form-grid" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="gsc_property">
                    <label class="field"><span>Property <small>empty = find it by itself</small></span><input class="input" name="gsc_property" value="<?= e(setting('gsc_property')) ?>" placeholder="sc-domain:<?= e(preg_replace('/^www\./', '', (string) parse_url(url(), PHP_URL_HOST))) ?>"></label>
                    <div style="align-self:end"><button class="btn btn--secondary btn--sm" type="submit">Save</button></div>
                </form>
                <form method="post" data-confirm="Disconnect Google Search Console?"><?= csrf_field() ?><input type="hidden" name="action" value="gsc_remove"><button class="btn btn--danger btn--sm" type="submit">Disconnect</button></form>
            </details>
        <?php else: ?>
            <ol class="steps">
                <li>Open <a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener">Google Cloud</a> with the Google account of your Search Console and create a project (any name, e.g. "Cake shop website").</li>
                <li>Open <a href="https://console.cloud.google.com/apis/library/searchconsole.googleapis.com" target="_blank" rel="noopener">Google Search Console API</a> and press <strong>Enable</strong>.</li>
                <li>Open <a href="https://console.cloud.google.com/iam-admin/serviceaccounts/create" target="_blank" rel="noopener">Service accounts &rarr; Create</a>, give it a name (e.g. "website") and press <strong>Done</strong>. No roles are needed.</li>
                <li>Click the new service account &rarr; <strong>Keys</strong> &rarr; <strong>Add key</strong> &rarr; <strong>Create new key</strong> &rarr; <strong>JSON</strong>. A small file downloads.</li>
                <li>Copy the service account email (it ends with <code>iam.gserviceaccount.com</code>). In <a href="https://search.google.com/search-console/users" target="_blank" rel="noopener">Search Console &rarr; Settings &rarr; Users and permissions</a>, press <strong>Add user</strong>, paste it and choose <strong>Owner</strong>.</li>
                <li>Choose the downloaded file here and press Connect.</li>
            </ol>
            <form method="post" enctype="multipart/form-data" class="ai-writer__actions"><?= csrf_field() ?><input type="hidden" name="action" value="gsc_connect">
                <input class="input" type="file" name="gsc_key" accept=".json,application/json" required style="max-width:340px">
                <button class="btn btn--primary btn--sm" type="submit">Connect</button>
            </form>
            <p class="hint" style="margin:0">The key file is saved encrypted on this website only.</p>
        <?php endif; ?>
    </div>

    <div class="card__body form-stack" style="border-top:1px solid var(--line)">
        <h2 class="card__subtitle">Bing (IndexNow)</h2>
        <form method="post" class="ai-writer__actions"><?= csrf_field() ?><input type="hidden" name="action" value="indexnow">
            <label class="switch"><input type="checkbox" name="indexnow_enabled" value="1" <?= checked(setting('indexnow_enabled', '1') === '1') ?> onchange="this.form.submit()"><span class="switch__track"></span><span>Send changed addresses to Bing and the other IndexNow search engines</span></label>
        </form>
        <p class="hint" style="margin:0">Nothing to set up. Bing checks the key file <a href="<?= e(url(indexnow_key() . '.txt')) ?>" target="_blank"><?= e(indexnow_key()) ?>.txt</a>. For reports, add the site once in <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a> (you can sign in with Google and import it from Search Console).</p>
    </div>

    <?php if ($log): ?>
        <div class="card__body" style="border-top:1px solid var(--line)">
            <h2 class="card__subtitle">Last sent</h2>
            <ul class="send-log">
                <?php foreach ($log as $row): ?>
                    <li class="<?= $row['ok'] ? 'is-ok' : 'is-bad' ?>"><span><?= e(date('j M, g:ia', strtotime($row['at']))) ?></span><?= e($row['text']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>
</section>
