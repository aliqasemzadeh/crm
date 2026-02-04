@blaze

{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
    if ($variant === 'solid') {
        throw new \Exception('The "solid" variant is not supported in Lucide.');
    }

    $classes = Flux::classes('shrink-0')
        ->add(match($variant) {
            'outline' => '[:where(&)]:size-6',
            'solid' => '[:where(&)]:size-6',
            'mini' => '[:where(&)]:size-5',
            'micro' => '[:where(&)]:size-4',
        });

    $strokeWidth = match ($variant) {
        'outline' => 2,
        'mini' => 2.25,
        'micro' => 2.5,
    };
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 512 512"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-slot="icon"
>
<g>
        <path
            d="M112.56 265.74v34.71c0 11.16 9.04 20.2 20.2 20.2h246.48c11.16 0 20.2-9.04 20.2-20.2v-88.9c0-11.16-9.04-20.2-20.2-20.2H132.76c-11.16 0-20.2 9.04-20.2 20.2v19.19"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <path
            d="m298.072 287.82 11.363-62.957c.284-1.474 2.34-1.612 2.818-.189l18.485 62.656c.448 1.334 2.339 1.325 2.773-.014l17.854-62.631c.463-1.428 2.52-1.312 2.82.159l12.036 62.977M196.177 229.195a32.18 32.18 0 0 0-18.073-5.521c-17.853 0-32.325 14.472-32.325 32.325s14.472 32.325 32.325 32.325c7.251 0 13.215-2.387 17.741-6.418a25.92 25.92 0 0 0 2.624-2.707M238.589 257.469l27.158 30.856M230.793 257.353v30.972M265.747 240.477c0 9.279-8.137 16.802-17.753 16.802-4.769 0-17.201.074-17.201.074l.021-33.678h17.18c9.616 0 17.753 7.522 17.753 16.802zM458.82 82.472c15.371 5.825 28.178 16.856 36.284 30.945-12.207 21.213-35.082 35.506-61.316 35.506s-49.109-14.293-61.316-35.506c8.105-14.089 20.913-25.12 36.284-30.945"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <path
            d="M398.79 16.76c10.32-5.89 22.26-9.26 35-9.26 39.05 0 70.71 31.66 70.71 70.71s-31.66 70.71-70.71 70.71-70.71-31.66-70.71-70.71c0-13.7 3.9-26.49 10.65-37.32"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <circle cx="433.789" cy="67.109" r="28.78"
                style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
                 stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                 class=""></circle>
        <path
            d="M458.82 438.049c15.371 5.825 28.178 16.856 36.284 30.945-12.207 21.213-35.082 35.506-61.316 35.506s-49.109-14.293-61.316-35.506c8.105-14.089 20.913-25.12 36.284-30.945"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <path
            d="M491.88 393.48c7.96 11.44 12.62 25.33 12.62 40.31 0 39.05-31.66 70.71-70.71 70.71s-70.71-31.66-70.71-70.71 31.66-70.71 70.71-70.71c11.46 0 22.29 2.73 31.86 7.58"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <circle cx="433.789" cy="422.687" r="28.78"
                style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
                 stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                 class=""></circle>
        <path
            d="M103.243 82.472c15.371 5.825 28.178 16.856 36.284 30.945-12.207 21.213-35.082 35.506-61.316 35.506s-49.109-14.293-61.316-35.506C25 99.328 37.808 88.297 53.179 82.472"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <circle cx="78.211" cy="78.211" r="70.711"
                style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
                 stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                 class=""></circle>
        <circle cx="78.211" cy="67.109" r="28.78"
                style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
                 stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                 class=""></circle>
        <path
            d="M103.243 438.049c15.371 5.825 28.178 16.856 36.284 30.945-12.207 21.213-35.082 35.506-61.316 35.506s-49.109-14.293-61.316-35.506c8.105-14.089 20.913-25.12 36.284-30.945"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
        <circle cx="78.211" cy="433.789" r="70.711"
                style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
                 stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                 class=""></circle>
        <circle cx="78.211" cy="422.687" r="28.78"
                style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
                 stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                 class=""></circle>
        <path
            d="M363.077 78.211H335.8c-15.621 0-28.285 12.663-28.285 28.285v84.854M148.923 78.211H176.2c15.621 0 28.285 12.663 28.285 28.285v84.854M363.077 433.789H335.8c-15.621 0-28.285-12.663-28.285-28.285V320.65M148.923 433.789H176.2c15.621 0 28.285-12.663 28.285-28.285V320.65"
            style="stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" fill="none"
             stroke-width="15" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
             class=""></path>
    </g></svg>
