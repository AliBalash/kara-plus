<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <!-- displays site properly based on user's device -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('assets/reserve/assets/images/favicon-32x32.png') }}" />
    <!-- Bootstrap css file -->
    <link rel="stylesheet" href="{{ asset('assets/reserve/styles/bootstrap.min.css') }}" />
    <!-- main style file -->
    <link rel="stylesheet" href="{{ asset('assets/reserve/styles/main.css') }}" />
    <title>Frontend Mentor | Multi-step form</title>

    <!-- Feel free to remove these styles or customise in your own stylesheet 👍 -->
    <style>
        .attribution {
            font-size: 11px;
            text-align: center;
        }

        .attribution a {
            color: hsl(228, 45%, 44%);
        }
    </style>
    @stack('styles')

</head>

<body>
    <!-- main container start -->
    <div class="">
        {{ $slot }}

    </div>




    <div class="attribution">
        Challenge by
        <a href="https://www.frontendmentor.io?ref=challenge" target="_blank">Frontend Mentor</a>. Coded by <a
            href="#">Abdo</a>.
    </div>
    <!-- main js -->
    <script src="{{ asset('assets/reserve/js/main.js') }}" defer></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('assets/reserve/js/bootstrap.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollableBox = document.querySelector('#month-plan');

            if (scrollableBox) {
                const scrollSequence = async () => {
                    const boxHeight = scrollableBox.scrollHeight;
                    const boxViewHeight = scrollableBox.clientHeight;

                    // محاسبه موقعیت وسط
                    const scrollToMiddle = (boxHeight - boxViewHeight) / 10;

                    // اسکرول به وسط و اضافه کردن افکت سایه
                    await smoothScroll(scrollableBox, scrollToMiddle, 0, 2000); // مدت زمان طولانی‌تر

                    // برگشت به بالا
                    await smoothScroll(scrollableBox, 0, 0, 1000); // مدت زمان طولانی‌تر
                };

                // تابع اسکرول نرم
                const smoothScroll = (element, top, left, duration) => {
                    return new Promise((resolve) => {
                        const startTop = element.scrollTop;
                        const startLeft = element.scrollLeft;
                        const changeTop = top - startTop;
                        const changeLeft = left - startLeft;
                        const startTime = performance.now();

                        const animateScroll = (currentTime) => {
                            const elapsedTime = currentTime - startTime;
                            const progress = Math.min(elapsedTime / duration, 1);

                            // استفاده از تابع ease-in-out برای انیمیشن نرم‌تر
                            const easeInOutQuad = (t) => (t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) *
                                t);

                            const easedProgress = easeInOutQuad(progress);
                            element.scrollTop = startTop + changeTop * easedProgress;
                            element.scrollLeft = startLeft + changeLeft * easedProgress;

                            if (progress < 1) {
                                requestAnimationFrame(animateScroll);
                            } else {
                                resolve();
                            }
                        };

                        requestAnimationFrame(animateScroll);
                    });
                };

                // شروع تسلسل اسکرول
                scrollSequence().then(() => {
                    scrollableBox.scrollTo({
                        top: 0,
                        left: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>

    @stack('scripts')

</body>

</html>
