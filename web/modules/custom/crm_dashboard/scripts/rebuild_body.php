<?php
$n = \Drupal\node\Entity\Node::load(146);
$b = $n->get('body')->value;

// Tách lấy đoạn từ đầu cho đến hết thẻ a của "Account"
$pos = strpos($b, 'id="card-activities"');
if ($pos === false) { die("Could not find activities card."); }

$search = '<a href="/user" class="qac-card" style="--cc:#64748b;--ci:#f1f5f9;">';
$pos2 = strpos($b, $search, $pos);
if ($pos2 === false) { die("Could not find user card."); }

$start_of_account = $pos2;
$end_of_account = strpos($b, '</a>', $start_of_account) + 4; // Bỏ qua đoạn rác đằng sau

$clean_html_start = substr($b, 0, $end_of_account);

$new_card = <<<'HTML'

    <a href="/admin/content/export" class="qac-card" style="--cc:#3b82f6;--ci:#eff6ff;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="download-cloud"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Export</div>
          <div class="qac-card-title">Export Data</div>
        </div>
      </div>
      <div class="qac-card-desc">Download CRM reports &amp; entities to CSV</div>
      <div class="qac-card-action"><span>Export now</span><i data-lucide="arrow-right"></i></div>
    </a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) lucide.createIcons();

  var isLoggedIn = document.body.classList.contains('user-logged-in');
  var isAdmin = document.body.classList.contains('role--administrator') ||
                document.body.classList.contains('role-administrator') ||
                (window.drupalSettings && window.drupalSettings.user &&
                 (window.drupalSettings.user.uid === '1' || window.drupalSettings.user.uid === 1));

  if (isLoggedIn) {
    // Swap hero to quick-add buttons
    var heroActions = document.getElementById('qac-hero-actions');
    if (heroActions) {
      heroActions.innerHTML =
        '<a href="/crm/realtime-chat" class="qac-btn qac-btn-outline">' +
          '<i data-lucide="messages-square"></i><span>Open Chat</span>' +
        '</a>' +
        '<a href="/crm/add/contact" class="qac-btn qac-btn-white">' +
          '<i data-lucide="user-plus"></i><span>New Contact</span>' +
        '</a>' +
        '<a href="/crm/add/organization" class="qac-btn qac-btn-outline">' +
          '<i data-lucide="building-2"></i><span>New Organization</span>' +
        '</a>' +
        '<a href="/crm/add/deal" class="qac-btn qac-btn-outline">' +
          '<i data-lucide="plus-circle"></i><span>New Deal</span>' +
        '</a>' +
        '<a href="/crm/add/activity" class="qac-btn qac-btn-outline">' +
          '<i data-lucide="calendar-plus"></i><span>New Activity</span>' +
        '</a>';
    }
    document.getElementById('qac-hero-title').textContent = 'Quick Access';
    document.getElementById('qac-hero-subtitle').textContent = 'Jump to any section of your CRM workspace';
  }

  if (isAdmin && isLoggedIn) {
    document.getElementById('qac-hero-title').textContent = 'Admin Quick Access';

    var updates = [
      { card: 'card-contacts',   href: '/crm/all-contacts',      title: 'All Contacts',      desc: 'All customers in the system',         action: 'View all' },
      { card: 'card-orgs',       href: '/crm/all-organizations',  title: 'All Organizations', desc: 'All companies in the system',          action: 'View all' },
      { card: 'card-deals',      href: '/crm/all-deals',          title: 'All Deals',         desc: 'All deals across the system',          action: 'View all' },
      { card: 'card-pipeline',   href: '/crm/all-pipeline',       title: 'All Pipeline',      desc: 'All deals across all stages',          action: 'View all' },
      { card: 'card-activities', href: '/crm/all-activities',     title: 'All Activities',    desc: 'All activities in the system',         action: 'View all' },
    ];

    updates.forEach(function (u) {
      var card = document.getElementById(u.card);
      if (card) card.href = u.href;
      var t = document.getElementById(u.card + '-title');
      if (t) t.textContent = u.title;
      var d = document.getElementById(u.card + '-desc');
      if (d) d.textContent = u.desc;
      var a = document.getElementById(u.card + '-action');
      if (a) a.textContent = u.action;
    });
  }

  if (window.lucide) lucide.createIcons();
});
</script>
HTML;

$final = $clean_html_start . $new_card;

$n->set('body', ['value' => $final, 'format' => 'full_html']);
$n->save();
echo "Rebuilt node 146 successfully.\n";
