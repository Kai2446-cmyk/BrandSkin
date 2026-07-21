@php
  $settings = $siteSettings ?? collect();
  $webName = $settings->has('web_name') ? trim((string) $settings['web_name']) : 'GlowSkin';
  $tagline = $settings->has('brand_tagline') ? trim((string) $settings['brand_tagline']) : 'Beauty Brand';
  $hasIdentityText = filled($webName) || filled($tagline);
  $logo = $settings['logo'] ?? 'assets/images/app_logo.png';
  $quickLinks = [
    ['label'=>'About Us','href'=>'#'],
    ['label'=>'Store Locator','href'=>'#'],
    ['label'=>'Beauty Consultant','href'=>'#'],
    ['label'=>'Skin Analyzer','href'=>url('/skin-analyzer')],
    ['label'=>'Careers','href'=>'#'],
  ];
  $categories = [
    ['label'=>'Makeup','href'=>url('/makeup')],
    ['label'=>'Skincare','href'=>url('/skincare')],
    ['label'=>'Sale','href'=>url('/sale')],
    ['label'=>'New Arrival','href'=>route('home').'#products'],
    ['label'=>'Best Seller','href'=>route('home').'#products'],
  ];
  $socialLinks = collect([
    ['label'=>'Instagram','key'=>'instagram','href'=>trim((string) ($settings['instagram'] ?? ''))],
    ['label'=>'TikTok','key'=>'tiktok','href'=>trim((string) ($settings['tiktok'] ?? ''))],
    ['label'=>'Facebook','key'=>'facebook','href'=>trim((string) ($settings['facebook'] ?? ''))],
    ['label'=>'Twitter/X','key'=>'x','href'=>trim((string) ($settings['twitter'] ?? ''))],
    ['label'=>'YouTube','key'=>'youtube','href'=>trim((string) ($settings['youtube'] ?? ''))],
  ])->filter(fn ($item) => filled($item['href']))->values();
@endphp
<footer class="relative footer-luxury" style="background:linear-gradient(135deg,#6F4A8E 0%,#7E58A3 52%,#8F67B2 100%);border-top:1px solid rgba(255,255,255,.18)">
  <div class="max-w-7xl mx-auto px-4 md:px-8 py-14 md:py-16">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 md:gap-8">
      <div class="col-span-2 md:col-span-1">
        <a href="{{ route('home') }}" class="footer-brand {{ $hasIdentityText ? '' : 'is-logo-only' }} mb-5">
          <img src="{{ \Illuminate\Support\Str::startsWith($logo, ['http://','https://']) ? $logo : asset($logo) }}" alt="{{ filled($webName) ? $webName.' Logo' : 'Website Logo' }}">
          @if($hasIdentityText)
            <span class="font-extrabold text-xl tracking-tight text-white uppercase" style="color:#FFFFFF">{{ $webName }}</span>
          @endif
        </a>
        <p class="text-sm leading-relaxed mb-6" style="color:rgba(255,255,255,0.72)">
          Premium cosmetics and skincare engineered for every skin type. Your glow, perfected.
        </p>
        @if($socialLinks->isNotEmpty())
        <div class="flex items-center gap-3">
          @foreach($socialLinks as $s)
            <a href="{{ $s['href'] }}" target="_blank" rel="noopener noreferrer" class="social-btn social-btn-{{ $s['key'] }}" aria-label="{{ $s['label'] }}">
              @if($s['key'] === 'instagram')
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.4" cy="6.6" r="1.2" fill="currentColor"/></svg>
              @elseif($s['key'] === 'facebook')
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14.2 8.1V6.6c0-.7.5-1.1 1.2-1.1H17V2.8c-.8-.1-1.6-.2-2.4-.2-2.5 0-4.1 1.5-4.1 4.2v1.3H7.8v3h2.7v10.3h3.2V11.1h2.7l.4-3h-2.6Z"/></svg>
              @elseif($s['key'] === 'tiktok')
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16.6 5.1c1 .9 2.1 1.4 3.4 1.5v3.1c-1.3 0-2.4-.3-3.5-1v6.1c0 3.7-2.4 6.1-6 6.1-3 0-5.4-2.1-5.4-5.2 0-3.3 2.6-5.5 6-5.2v3.2c-1.5-.3-2.8.4-2.8 1.9 0 1.2 1 2 2.2 2 1.4 0 2.5-.8 2.5-2.8V2.9h3.1c.1.8.2 1.5.5 2.2Z"/></svg>
              @elseif($s['key'] === 'x')
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14.4 10.6 21.3 3h-3.1l-5.2 5.8L8.8 3H3l7 9.5L2.7 21h3.1l5.7-6.4 4.7 6.4H22l-7.6-10.4Zm-2 2.3-1.3-1.8L6.6 5.4h1.1l4.3 5.7 1.3 1.8 4.9 6.6h-1.1l-4.7-6.6Z"/></svg>
              @else
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21.6 7.2s-.2-1.5-.8-2.1c-.8-.8-1.6-.8-2-.9C16 4 12 4 12 4s-4 0-6.8.2c-.4.1-1.3.1-2 .9-.6.6-.8 2.1-.8 2.1S2.2 9 2.2 10.8v1.7c0 1.8.2 3.6.2 3.6s.2 1.5.8 2.1c.8.8 1.8.8 2.2.9 1.6.2 6.6.2 6.6.2s4 0 6.8-.2c.4-.1 1.3-.1 2-.9.6-.6.8-2.1.8-2.1s.2-1.8.2-3.6v-1.7c0-1.8-.2-3.6-.2-3.6ZM10.2 14.9V8.6l5.6 3.2-5.6 3.1Z"/></svg>
              @endif
            </a>
          @endforeach
        </div>
        @endif
      </div>

      <div><h4 class="footer-title" style="color:#F2E8FF">Quick Links</h4><ul class="space-y-3">@foreach($quickLinks as $link)<li><a href="{{ $link['href'] }}" class="footer-link" style="color:rgba(255,255,255,.72)">{{ $link['label'] }}</a></li>@endforeach</ul></div>
      <div><h4 class="footer-title" style="color:#F2E8FF">Categories</h4><ul class="space-y-3">@foreach($categories as $cat)<li><a href="{{ $cat['href'] }}" class="footer-link" style="color:rgba(255,255,255,.72)">{{ $cat['label'] }}</a></li>@endforeach</ul></div>
      <div><h4 class="footer-title" style="color:#F2E8FF">Customer Care</h4><ul class="space-y-3">@foreach(['FAQs','Shipping Policy','Return Policy','Track Order','Contact Us'] as $item)<li><a href="#" class="footer-link" style="color:rgba(255,255,255,.72)">{{ $item }}</a></li>@endforeach</ul></div>
    </div>
  </div>
  <div style="border-top:1px solid rgba(255,255,255,0.16)"><div class="max-w-7xl mx-auto px-4 md:px-8 py-5 flex flex-col md:flex-row items-center justify-between gap-4"><p class="text-xs font-medium" style="color:rgba(255,255,255,0.6)">© 2026{{ filled($webName) ? ' '.$webName : '' }}. All rights reserved.</p><div class="flex items-center gap-4 md:gap-6">@foreach(['Privacy Policy','Terms of Service','Cookie Policy'] as $i=>$item)<a href="#" class="text-xs font-medium transition-colors hover:text-[var(--primary)]" style="color:rgba(255,255,255,0.6)">{{ $item }}</a>@if($i < 2)<span style="color:rgba(255,255,255,0.24)">·</span>@endif @endforeach</div></div></div>
</footer>
