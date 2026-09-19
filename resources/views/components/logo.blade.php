@props(['size' => 13])
<a href="{{ route('home') }}" class="flex" style="align-items:center;text-decoration:none;padding-left:10px;">
    <img src="{{ asset('images/logo-wordmark-light.png') }}" alt="FRAMEBLADESCORE" class="logo-light" style="height:{{ $size }}px;width:auto;flex-shrink:0;">
    <img src="{{ asset('images/logo-wordmark-dark.png') }}" alt="FRAMEBLADESCORE" class="logo-dark" style="height:{{ $size }}px;width:auto;flex-shrink:0;">
</a>
