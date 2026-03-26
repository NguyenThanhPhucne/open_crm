<?php
$n = \Drupal\node\Entity\Node::load(146);
$b = $n->get('body')->value;

// Đoạn HTML gây lỗi nằm bên trong qac-card-desc
$b = preg_replace('/<a class="qac-card"[^>]*href="\/crm\/dashboard".*?<\/a>/is', '', $b);
$b = preg_replace('/<a class="qac-card"[^>]*href="\/crm\/my-contacts".*?<\/a>/is', '', $b);
// Xóa luôn phần export cũ bị lặp
$b = preg_replace('/<a href="\/admin\/content\/export"[^>]*>.*?<\/a>/is', '', $b);

// Nối lại thẻ bài export xịn ở cuối
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
HTML;

$b = str_replace('</div>
</div>

<script>', $new_card . "\n  </div>\n</div>\n\n<script>", $b);

$n->set('body', ['value' => $b, 'format' => 'full_html']);
$n->save();
echo "Cleaned and saved node 146.";
