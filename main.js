// The Kitchen - Main JavaScript
$(document).ready(function () {

    // Category chip filter
    $('.cat-chip').on('click', function () {
        $('.cat-chip').removeClass('active');
        $(this).addClass('active');
        var cat = $(this).data('cat');
        if (cat === 'all') {
            $('.listing-card-wrap').show();
        } else {
            $('.listing-card-wrap').hide();
            $('.listing-card-wrap[data-cat="' + cat + '"]').show();
        }
    });

    // Search filter
    $('#searchInput').on('keyup', function () {
        var val = $(this).val().toLowerCase();
        $('.listing-card-wrap').each(function () {
            var title = $(this).find('.listing-card-title').text().toLowerCase();
            $(this).toggle(title.includes(val));
        });
    });

    // Image preview on sell form
    $('#listingImage').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#imagePreview').attr('src', e.target.result).show();
                $('#imagePlaceholder').hide();
            };
            reader.readAsDataURL(file);
        }
    });

    // Form validation
    $('form[data-validate]').on('submit', function (e) {
        var valid = true;
        $(this).find('[required]').each(function () {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        if (!valid) {
            e.preventDefault();
            showAlert('Please fill in all required fields.', 'error');
        }
    });

    // Alert helper
    window.showAlert = function (msg, type) {
        var cls = type === 'error' ? 'error' : 'success';
        var html = '<div class="alert-kitchen ' + cls + ' mb-3">' + msg + '</div>';
        $('#alertArea').html(html);
        setTimeout(function () { $('#alertArea').html(''); }, 4000);
    };

    // Auto-dismiss alerts
    setTimeout(function () { 
        $('.alert-kitchen').fadeOut(400); 
    }, 4000);

    // Price format
    $('.listing-price-input').on('input', function () {
        var val = parseFloat($(this).val());
        if (!isNaN(val)) {
            $('#pricePreview').text('R ' + val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
        }
    });

    // Confirm delete
    $(document).on('click', '.btn-delete', function (e) {
        if (!confirm('Are you sure you want to delete this? This cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Smooth scroll to section
    $('a[href^="#"]').on('click', function (e) {
        var target = $($(this).attr('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 400);
        }
    });

    // Listing card hover effect
    $('.listing-card').on('mouseenter', function () {
        $(this).find('.btn-kitchen-primary').css('opacity', '1');
    }).on('mouseleave', function () {
        $(this).find('.btn-kitchen-primary').css('opacity', '');
    });

    // Star rating widget
    $('.star-rating .star').on('click', function () {
        var val = $(this).data('value');
        $('#ratingInput').val(val);
        $('.star-rating .star').each(function () {
            $(this).toggleClass('active', $(this).data('value') <= val);
        });
    });

});

// Format price with ZAR
function formatPrice(amount) {
    return 'R ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}