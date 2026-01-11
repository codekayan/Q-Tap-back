<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>VRISTO - Multipurpose Tailwind Dashboard Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="{{ url('resources/views/assets/favicon.png') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" type="text/css" media="screen" href="{{ url('resources/views/assets/css/style.css') }}" />
</head>

<body x-data="main" class="relative overflow-x-hidden font-nunito text-sm font-normal antialiased"
    :class="[$store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ? 'dark' : '',
        $store.app.menu, $store.app.layout, $store.app.rtlClass
    ]">


    @if (session('success'))
        <!-- component -->
        <div class="bg-gray-100 h-screen" style="display: flex;justify-content: center;">
            <div class="bg-white p-6  md:mx-auto">
                <svg viewBox="0 0 24 24" class="text-green-600 w-16 h-16 mx-auto my-6" style="color: green">
                    <path fill="currentColor"
                        d="M12,0A12,12,0,1,0,24,12,12.014,12.014,0,0,0,12,0Zm6.927,8.2-6.845,9.289a1.011,1.011,0,0,1-1.43.188L5.764,13.769a1,1,0,1,1,1.25-1.562l4.076,3.261,6.227-8.451A1,1,0,1,1,18.927,8.2Z">
                    </path>
                </svg>
                <div class="text-center">
                    <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Payment Done!</h3>
                    <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">{{ session('success') }}
                    </h3>
                    <p> Have a great day! </p>
                    <div class="py-10 text-center">

                    </div>
                </div>
            </div>
        </div>
    @elseif (session('error'))
        <!-- component -->
        <div class="bg-gray-100 h-screen" style="display: flex;justify-content: center;">
            <div class="bg-white p-6  md:mx-auto">
                <svg viewBox="0 0 24 24" class="text-green-600 w-16 h-16 mx-auto my-6" style="color: red">
                    <path fill="currentColor"
                        d="M12,0A12,12,0,1,0,24,12,12.014,12.014,0,0,0,12,0ZM16.95,7.05a1,1,0,0,1,0,1.414L13.414,12l3.536,3.536a1,1,0,0,1-1.414,1.414L12,13.414,8.464,16.95a1,1,0,0,1-1.414-1.414L10.586,12,7.05,8.464A1,1,0,0,1,8.464,7.05L12,10.586l3.536-3.536A1,1,0,0,1,16.95,7.05Z" />
                </svg>
                <div class="text-center">
                    <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Payment false!</h3>
                    <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">{{ session('error') }}
                    </h3>
                    <p> Have a great day! </p>
                    {{-- <div class="py-10 text-center">
                        <a href="{{ route('login') }}"
                        class="btn btn-gradient mx-auto !mt-7 w-max border-0 uppercase shadow-none">LogIn</a>
                    </div> --}}

                </div>
            </div>
        </div>
    @endif





    <script src="{{ url('resources/views/assets/js/alpine-collaspe.min.js') }}"></script>
    <script src="{{ url('resources/views/assets/js/alpine-persist.min.js') }}"></script>
    <script defer src="{{ url('resources/views/assets/js/alpine-ui.min.js') }}"></script>
    <script defer src="{{ url('resources/views/assets/js/alpine-focus.min.js') }}"></script>
    <script defer src="{{ url('resources/views/assets/js/alpine.min.js') }}"></script>

    <script src="{{ url('resources/views/assets/js/custom.js') }}"></script>

    <script>
        // main section
        document.addEventListener('alpine:init', () => {
            Alpine.data('scrollToTop', () => ({
                showTopButton: false,
                init() {
                    window.onscroll = () => {
                        this.scrollFunction();
                    };
                },

                scrollFunction() {
                    if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
                        this.showTopButton = true;
                    } else {
                        this.showTopButton = false;
                    }
                },

                goToTop() {
                    document.body.scrollTop = 0;
                    document.documentElement.scrollTop = 0;
                },
            }));

            Alpine.data('auth', () => ({
                languages: [{
                        id: 1,
                        key: 'Chinese',
                        value: 'zh',
                    },
                    {
                        id: 2,
                        key: 'Danish',
                        value: 'da',
                    },
                    {
                        id: 3,
                        key: 'English',
                        value: 'en',
                    },
                    {
                        id: 4,
                        key: 'French',
                        value: 'fr',
                    },
                    {
                        id: 5,
                        key: 'German',
                        value: 'de',
                    },
                    {
                        id: 6,
                        key: 'Greek',
                        value: 'el',
                    },
                    {
                        id: 7,
                        key: 'Hungarian',
                        value: 'hu',
                    },
                    {
                        id: 8,
                        key: 'Italian',
                        value: 'it',
                    },
                    {
                        id: 9,
                        key: 'Japanese',
                        value: 'ja',
                    },
                    {
                        id: 10,
                        key: 'Polish',
                        value: 'pl',
                    },
                    {
                        id: 11,
                        key: 'Portuguese',
                        value: 'pt',
                    },
                    {
                        id: 12,
                        key: 'Russian',
                        value: 'ru',
                    },
                    {
                        id: 13,
                        key: 'Spanish',
                        value: 'es',
                    },
                    {
                        id: 14,
                        key: 'Swedish',
                        value: 'sv',
                    },
                    {
                        id: 15,
                        key: 'Turkish',
                        value: 'tr',
                    },
                    {
                        id: 16,
                        key: 'Arabic',
                        value: 'ae',
                    },
                ],
            }));
        });
    </script>
</body>

</html>
