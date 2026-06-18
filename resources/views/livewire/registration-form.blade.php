<div>
    @if($done && $step === 4)
        {{-- Confirmation --}}
        <div style="border-bottom:2px solid var(--ink);background:var(--lime);color:#0a0a0b;position:relative;overflow:hidden;">
            <div style="position:absolute;inset:0;background-image:radial-gradient(circle,rgba(0,0,0,0.12) 1px,transparent 1px);background-size:16px 16px;pointer-events:none;"></div>
            <div class="wrap center col" style="position:relative;padding:70px 0;text-align:center;gap:10px;">
                <span class="sticker" style="--rot:-4deg;background:#0a0a0b;color:var(--lime);font-size:13px;">ENTRY CONFIRMED</span>
                <h1 class="display" style="font-size:clamp(44px,8vw,96px);margin:10px 0 0;">You're In.</h1>
                <p style="font-size:17px;max-width:460px;">
                    See you at {{ $currentEvent?->venue }}, {{ $currentEvent?->city }}.
                    Check your email for the rider pack and schedule.
                </p>
            </div>
        </div>
        <div class="wrap section" style="max-width:720px;padding-top:40px;">
            <div class="panel" style="overflow:hidden;">
                <div class="between" style="padding:16px 20px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                    <span class="kicker">DIGITAL RIDER PASS</span>
                    <span class="mono tnum" style="font-size:14px;font-weight:700;color:var(--lime);">{{ $entryCode }}</span>
                </div>
                <div class="flex" style="flex-wrap:wrap;">
                    <div style="flex:1 1 280px;padding:24px;">
                        @foreach([['RIDER',$name],['EVENT',$currentEvent?->title],['CATEGORY',$category],['CITY',$city],['PAYMENT','Transfer Bank'],['STATUS','PENDING APPROVAL']] as [$l,$v])
                            <div class="between" style="padding:11px 0;border-bottom:1px solid var(--line);">
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $l }}</span>
                                <span class="label" style="font-size:14px;text-align:right;">{{ $v }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="ph halftone" data-ph="QR Code" style="flex:0 0 200px;min-height:200px;border-left:2px solid var(--ink);">
                        <div style="width:120px;height:120px;background:var(--ink);position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:grid;grid-template-columns:repeat(7,1fr);padding:8px;gap:2px;">
                            @for($qi = 0; $qi < 49; $qi++)
                                <div style="background:{{ rand(0,1) ? 'var(--bg)' : 'transparent' }};"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-m" style="margin-top:24px;flex-wrap:wrap;">
                <a href="{{ route('events.show', $eventSlug) }}" class="btn btn-lime">View Event →</a>
                <a href="{{ route('notifications', $entryCode) }}" class="btn btn-ghost">View Notifications</a>
                <a href="{{ route('home') }}" class="btn btn-ghost">Back Home</a>
            </div>
        </div>
    @else
        {{-- Registration form --}}
        <div class="halftone" style="border-bottom:2px solid var(--ink);">
            <div class="wrap" style="padding:48px 0 40px;">
                <div class="eyebrow-row" style="margin-bottom:12px;">
                    <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">ENTRY /</span>
                    <span class="kicker">COMPETITOR REGISTRATION</span>
                </div>
                <h1 class="display" style="font-size:clamp(40px,7vw,84px);">Register</h1>
                <p class="dim" style="margin-top:10px;max-width:520px;">Lock your spot on the start list. Five quick steps.</p>
            </div>
        </div>

        <div class="wrap section" style="padding-top:36px;max-width:880px;">
            {{-- Progress --}}
            <div class="flex" style="margin-bottom:34px;gap:0;">
                @foreach(['Personal','Category','Emergency','Payment','Confirm'] as $i => $s)
                    <div class="flex" style="flex:1;align-items:center;">
                        <div class="col center" style="gap:7px;flex-shrink:0;">
                            <div class="center" style="
                                width:38px;height:38px;border-radius:999px;border:2px solid var(--ink);
                                font-family:'Bebas Neue',sans-serif;font-size:16px;
                                background:{{ $i < $step ? 'var(--ink)' : ($i === $step ? 'var(--lime)' : 'var(--panel)') }};
                                color:{{ $i < $step ? 'var(--bg)' : ($i === $step ? '#0a0a0b' : 'var(--ink-dim)') }};
                            ">{{ $i < $step ? '✓' : $i+1 }}</div>
                            <span class="mono step-label" style="font-size:9px;letter-spacing:0.1em;color:{{ $i === $step ? 'var(--ink)' : 'var(--ink-faint)' }};">{{ strtoupper($s) }}</span>
                        </div>
                        @if(!$loop->last)
                            <div style="flex:1;height:2px;background:{{ $i < $step ? 'var(--ink)' : 'var(--line)' }};margin:0 6px 18px;"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="panel" style="padding:clamp(20px,4vw,36px);">

                {{-- Step 0: Personal --}}
                @if($step === 0)
                    <div class="flex" style="align-items:baseline;gap:12px;margin-bottom:26px;">
                        <span class="mono" style="font-size:12px;color:var(--lime);">01</span>
                        <h2 class="display" style="font-size:clamp(24px,3.2vw,34px);">Personal Data</h2>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="prof-grid">

                        {{-- Name --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['name']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                FULL NAME *{{ isset($errors['name']) ? ' — '.$errors['name'] : '' }}
                            </span>
                            <input wire:model.live="name" type="text" placeholder="e.g. Rama Adhyaksa"
                                class="field-input {{ isset($errors['name']) ? 'error' : '' }}" />
                        </label>

                        {{-- Email --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['email']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                EMAIL *{{ isset($errors['email']) ? ' — '.$errors['email'] : '' }}
                            </span>
                            <input wire:model.live="email" type="email" placeholder="you@email.com"
                                class="field-input {{ isset($errors['email']) ? 'error' : '' }}" />
                        </label>

                        {{-- Phone / WA — kode negara dipisah --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['phone']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                PHONE / WA *{{ isset($errors['phone']) ? ' — '.$errors['phone'] : '' }}
                            </span>
                            <div class="flex" style="gap:0;">
                                <select wire:model="phoneCode" style="
                                    padding:11px 10px;background:var(--bg-2);
                                    border:2px solid {{ isset($errors['phone']) ? 'var(--red)' : 'var(--ink)' }};
                                    border-right:none;border-radius:3px 0 0 3px;
                                    color:var(--ink);font-family:inherit;font-size:13px;outline:none;
                                    flex-shrink:0;
                                ">
                                    <option value="+62">🇮🇩 +62</option>
                                    <option value="+60">🇲🇾 +60</option>
                                    <option value="+65">🇸🇬 +65</option>
                                    <option value="+63">🇵🇭 +63</option>
                                    <option value="+66">🇹🇭 +66</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+61">🇦🇺 +61</option>
                                </select>
                                <input wire:model.live="phoneNumber" type="tel" placeholder="8123456789"
                                    style="
                                        flex:1;padding:11px 14px;background:var(--surface);
                                        border:2px solid {{ isset($errors['phone']) ? 'var(--red)' : 'var(--ink)' }};
                                        border-radius:0 3px 3px 0;color:var(--ink);
                                        font-family:inherit;font-size:14px;outline:none;
                                    "
                                    onfocus="this.style.borderColor='var(--lime)';this.previousElementSibling.style.borderColor='var(--lime)'"
                                    onblur="this.style.borderColor='{{ isset($errors['phone']) ? 'var(--red)' : 'var(--ink)' }}';this.previousElementSibling.style.borderColor='{{ isset($errors['phone']) ? 'var(--red)' : 'var(--ink)' }}'">
                            </div>
                        </label>

                        {{-- Date of Birth --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['dob']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                DATE OF BIRTH *{{ isset($errors['dob']) ? ' — '.$errors['dob'] : '' }}
                            </span>
                            <input wire:model.live="dob" type="date"
                                class="field-input {{ isset($errors['dob']) ? 'error' : '' }}" />
                        </label>

                        {{-- City --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['city']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                CITY *{{ isset($errors['city']) ? ' — '.$errors['city'] : '' }}
                            </span>
                            <input wire:model.live="city" type="text" placeholder="e.g. Jakarta"
                                class="field-input {{ isset($errors['city']) ? 'error' : '' }}" />
                        </label>

                    </div>
                @endif

                {{-- Step 1: Category --}}
                @if($step === 1)
                    <div class="flex" style="align-items:baseline;gap:12px;margin-bottom:26px;">
                        <span class="mono" style="font-size:12px;color:var(--lime);">02</span>
                        <h2 class="display" style="font-size:clamp(24px,3.2vw,34px);">Event & Category</h2>
                    </div>
                    <label class="col" style="gap:7px;margin-bottom:22px;">
                        <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:var(--ink-dim);">EVENT *</span>
                        <select wire:model.live="eventSlug" class="field-input">
                            @foreach($events as $ev)
                                <option value="{{ $ev->slug }}">{{ $ev->title }} — {{ $ev->date_label }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if($currentEvent)
                        <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['category']) ? 'var(--red)' : 'var(--ink-dim)' }};display:block;margin-bottom:10px;">
                            CATEGORY *{{ isset($errors['category']) ? ' — '.$errors['category'] : '' }}
                        </span>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:22px;">
                            @foreach($currentEvent->categories as $cat)
                                @php $labels = ['STREET'=>'Street','PARK'=>'Park','VERT'=>'Vert','FLAT'=>'Flatland']; @endphp
                                <button wire:click="$set('category','{{ $cat }}')" class="panel col" style="
                                    padding:18px;gap:8px;align-items:flex-start;cursor:pointer;
                                    border-color:{{ $category === $cat ? 'var(--lime)' : 'var(--ink)' }};
                                    box-shadow:{{ $category === $cat ? '4px 4px 0 var(--lime)' : 'var(--paper-shadow)' }};
                                ">
                                    <x-cat-badge :cat="$cat" />
                                    <span class="display" style="font-size:24px;">{{ $labels[$cat] ?? $cat }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div style="margin-top:22px;">
                        <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['competitionCategory']) ? 'var(--red)' : 'var(--ink-dim)' }};display:block;margin-bottom:10px;">
                            COMPETITION LEVEL *{{ isset($errors['competitionCategory']) ? ' — '.$errors['competitionCategory'] : '' }}
                        </span>
                        <div class="flex gap-s" style="flex-wrap:wrap;">
                            @foreach(['Beginner','Open','Pro'] as $opt)
                                <button wire:click="$set('competitionCategory','{{ $opt }}')" class="panel col" style="
                                    flex:1;min-width:110px;padding:14px 16px;gap:6px;align-items:flex-start;cursor:pointer;
                                    border-color:{{ $competitionCategory === $opt ? 'var(--lime)' : 'var(--ink)' }};
                                    box-shadow:{{ $competitionCategory === $opt ? '4px 4px 0 var(--lime)' : 'var(--paper-shadow)' }};
                                ">
                                    <span class="display" style="font-size:18px;">{{ $opt }}</span>
                                    <span class="mono dim" style="font-size:9px;">
                                        {{ $opt === 'Beginner' ? 'Entry level' : ($opt === 'Open' ? 'All skills welcome' : 'Elite & competitive') }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Step 2: Emergency --}}
                @if($step === 2)
                    <div class="flex" style="align-items:baseline;gap:12px;margin-bottom:26px;">
                        <span class="mono" style="font-size:12px;color:var(--lime);">03</span>
                        <h2 class="display" style="font-size:clamp(24px,3.2vw,34px);">Emergency Contact</h2>
                    </div>
                    <p class="dim" style="font-size:14px;margin-bottom:20px;">Required for all competitors. This person is contacted only in case of injury.</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="prof-grid">

                        {{-- Contact Name --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['ecName']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                CONTACT NAME *{{ isset($errors['ecName']) ? ' — '.$errors['ecName'] : '' }}
                            </span>
                            <input wire:model="ecName" type="text" placeholder="e.g. Budi Santoso"
                                class="field-input {{ isset($errors['ecName']) ? 'error' : '' }}" />
                        </label>

                        {{-- Contact Phone -- kode negara dipisah --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['ecPhone']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                CONTACT PHONE *{{ isset($errors['ecPhone']) ? ' — '.$errors['ecPhone'] : '' }}
                            </span>
                            <div class="flex" style="gap:0;">
                                <select wire:model="ecPhoneCode" style="
                                    padding:11px 10px;background:var(--bg-2);
                                    border:2px solid {{ isset($errors['ecPhone']) ? 'var(--red)' : 'var(--ink)' }};
                                    border-right:none;border-radius:3px 0 0 3px;
                                    color:var(--ink);font-family:inherit;font-size:13px;outline:none;
                                    flex-shrink:0;
                                ">
                                    <option value="+62">🇮🇩 +62</option>
                                    <option value="+60">🇲🇾 +60</option>
                                    <option value="+65">🇸🇬 +65</option>
                                    <option value="+63">🇵🇭 +63</option>
                                    <option value="+66">🇹🇭 +66</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+61">🇦🇺 +61</option>
                                </select>
                                <input wire:model.live="ecPhoneNum" type="tel" placeholder="8123456789"
                                    style="
                                        flex:1;padding:11px 14px;background:var(--surface);
                                        border:2px solid {{ isset($errors['ecPhone']) ? 'var(--red)' : 'var(--ink)' }};
                                        border-radius:0 3px 3px 0;color:var(--ink);
                                        font-family:inherit;font-size:14px;outline:none;
                                    "
                                    onfocus="this.style.borderColor='var(--lime)';this.previousElementSibling.style.borderColor='var(--lime)'"
                                    onblur="this.style.borderColor='{{ isset($errors['ecPhone']) ? 'var(--red)' : 'var(--ink)' }}';this.previousElementSibling.style.borderColor='{{ isset($errors['ecPhone']) ? 'var(--red)' : 'var(--ink)' }}'">
                            </div>
                        </label>

                        {{-- Relationship --}}
                        <label class="col" style="gap:7px;">
                            <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['ecRelation']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                                RELATIONSHIP *{{ isset($errors['ecRelation']) ? ' — '.$errors['ecRelation'] : '' }}
                            </span>
                            <input wire:model="ecRelation" type="text" placeholder="e.g. Parent, Sibling"
                                class="field-input {{ isset($errors['ecRelation']) ? 'error' : '' }}" />
                        </label>

                    </div>
                @endif

                {{-- Step 3: Payment --}}
                @if($step === 3)
                    <div class="flex" style="align-items:baseline;gap:12px;margin-bottom:26px;">
                        <span class="mono" style="font-size:12px;color:var(--lime);">04</span>
                        <h2 class="display" style="font-size:clamp(24px,3.2vw,34px);">Payment</h2>
                    </div>
                    <div class="between" style="padding:16px 18px;border:2px solid var(--ink);border-radius:3px;margin-bottom:20px;background:var(--bg-2);">
                        <div class="col">
                            <span class="mono dim" style="font-size:10px;letter-spacing:0.14em;">ENTRY FEE · {{ strtoupper($currentEvent?->title ?? '') }}</span>
                            <span class="label" style="font-size:14px;">{{ $category }} Division</span>
                        </div>
                        <span class="display tnum" style="font-size:30px;color:var(--lime);">Rp 350.000</span>
                    </div>
                    <div style="margin-bottom:20px;padding:14px 16px;background:var(--bg-2);border:2px solid var(--line);border-radius:3px;display:flex;align-items:center;gap:12px;">
                        <span style="font-size:20px;">🏦</span>
                        <div class="col" style="gap:3px;">
                            <span class="label" style="font-size:13px;">Transfer Bank / Virtual Account</span>
                            <span class="mono dim" style="font-size:11px;">Konfirmasi pembayaran akan dikirim via notifikasi setelah admin verifikasi.</span>
                        </div>
                    </div>
                    <div style="margin-top:20px;">
                        <span class="mono" style="font-size:10px;letter-spacing:0.14em;color:{{ isset($errors['payFile']) ? 'var(--red)' : 'var(--ink-dim)' }};display:block;margin-bottom:8px;">
                            UPLOAD PAYMENT PROOF *{{ isset($errors['payFile']) ? ' — '.$errors['payFile'] : '' }}
                        </span>
                        <label class="center col" style="
                            padding:34px;border:2px dashed {{ isset($errors['payFile']) ? 'var(--red)' : 'var(--line-strong)' }};
                            border-radius:3px;cursor:pointer;gap:10px;
                            background:{{ $payFile ? 'color-mix(in srgb,var(--lime) 10%,transparent)' : 'transparent' }};
                        ">
                            <input wire:model="payFile" type="file" accept="image/*" style="display:none;" />
                            <span style="font-size:28px;">{{ $payFile ? '✓' : '↑' }}</span>
                            <span class="label" style="font-size:14px;">{{ $payFile ? $payFile->getClientOriginalName() : 'Click to upload receipt' }}</span>
                            <span class="mono dim" style="font-size:10px;">JPG / PNG · MAX 5MB</span>
                        </label>
                    </div>
                    <label class="flex" style="gap:10px;margin-top:20px;cursor:pointer;align-items:flex-start;">
                        <input wire:model="agree" type="checkbox" style="width:20px;height:20px;margin-top:2px;accent-color:var(--lime);" />
                        <span style="font-size:13px;line-height:1.5;color:{{ isset($errors['agree']) ? 'var(--red)' : 'var(--ink-dim)' }};">
                            I agree to the competition rules, waiver of liability, and confirm all information is accurate.
                        </span>
                    </label>
                @endif

                {{-- Nav buttons --}}
                <div class="between" style="margin-top:30px;padding-top:22px;border-top:1px solid var(--line);">
                    <button wire:click="back" class="btn btn-ghost" {{ $step === 0 ? 'disabled' : '' }}>← Back</button>
                    <button wire:click="next" class="btn btn-lime" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $step === 3 ? 'Submit Entry →' : 'Continue →' }}</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
