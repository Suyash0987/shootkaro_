/**
 * Shoot Karo - Vanilla JavaScript Core
 * Handles responsive navigation, dynamic location filtering,
 * multi-step booking logic, and mock payment simulation.
 */

document.addEventListener('DOMContentLoaded', function () {
  initMobileNav();
  initLocationFilter();
  initBookingStepper();
  initModals();
  initImagePreviews();
});

/* -------------------------------------------------------------
   Mobile Navigation Toggle
------------------------------------------------------------- */
function initMobileNav() {
  const menuBtn = document.querySelector('.mobile-menu-btn');
  const mainNav = document.querySelector('.main-nav');

  if (menuBtn && mainNav) {
    menuBtn.addEventListener('click', function () {
      mainNav.classList.toggle('open');
    });
  }
}

/* -------------------------------------------------------------
   Dynamic Location Filter (City -> Area)
------------------------------------------------------------- */
function initLocationFilter() {
  const citySelect = document.getElementById('search-city');
  const areaSelect = document.getElementById('search-area');

  if (!citySelect || !areaSelect) return;

  citySelect.addEventListener('change', function () {
    const selectedCityId = this.value;
    const areaOptions = areaSelect.querySelectorAll('option');

    let visibleCount = 0;
    areaOptions.forEach(function (opt) {
      if (!opt.value) return; // keep "All Areas"
      const cityId = opt.getAttribute('data-city-id');
      if (!selectedCityId || cityId === selectedCityId) {
        opt.style.display = '';
        visibleCount++;
      } else {
        opt.style.display = 'none';
      }
    });

    // Reset area selection if hidden
    const currentSelected = areaSelect.selectedOptions[0];
    if (currentSelected && currentSelected.style.display === 'none') {
      areaSelect.value = '';
    }
  });
}

/* -------------------------------------------------------------
   Multi-Step Booking Form Engine
------------------------------------------------------------- */
function initBookingStepper() {
  const bookingForm = document.getElementById('booking-multistep-form');
  if (!bookingForm) return;

  let currentStep = 1;
  const totalSteps = 5;

  const stepPanels = document.querySelectorAll('.booking-step-panel');
  const stepIndicators = document.querySelectorAll('.step-item');
  const nextBtns = document.querySelectorAll('.btn-step-next');
  const prevBtns = document.querySelectorAll('.btn-step-prev');

  function showStep(step) {
    stepPanels.forEach(function (panel) {
      const pStep = parseInt(panel.getAttribute('data-step'), 10);
      if (pStep === step) {
        panel.style.display = 'block';
      } else {
        panel.style.display = 'none';
      }
    });

    stepIndicators.forEach(function (indicator) {
      const iStep = parseInt(indicator.getAttribute('data-step'), 10);
      indicator.classList.remove('active', 'completed');
      if (iStep === step) {
        indicator.classList.add('active');
      } else if (iStep < step) {
        indicator.classList.add('completed');
      }
    });

    // If reaching review step, recalculate summary
    if (step === 4 || step === 5) {
      calculateBookingFinancials();
    }

    window.scrollTo({ top: bookingForm.offsetTop - 80, behavior: 'smooth' });
  }

  // Next Step validation
  nextBtns.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (validateStep(currentStep)) {
        if (currentStep < totalSteps) {
          currentStep++;
          showStep(currentStep);
        }
      }
    });
  });

  // Previous Step
  prevBtns.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
      }
    });
  });

  // Payment Method Tab Switches
  const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
  paymentMethodRadios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      const val = this.value;
      const upiBox = document.getElementById('payment-details-upi');
      const cardBox = document.getElementById('payment-details-card');
      const payLaterBox = document.getElementById('payment-details-later');

      if (upiBox) upiBox.style.display = (val === 'upi') ? 'block' : 'none';
      if (cardBox) cardBox.style.display = (val === 'card') ? 'block' : 'none';
      if (payLaterBox) payLaterBox.style.display = (val === 'cash_pay_later') ? 'block' : 'none';
    });
  });

  function validateStep(step) {
    const activePanel = document.querySelector(`.booking-step-panel[data-step="${step}"]`);
    if (!activePanel) return true;

    // Step 1: Customer & Shoot Details
    if (step === 1) {
      const requiredInputs = activePanel.querySelectorAll('input[required], select[required]');
      for (let input of requiredInputs) {
        if (!input.value.trim()) {
          input.focus();
          alert('Please fill in all required event and contact details.');
          return false;
        }
      }
      const shootDateInput = activePanel.querySelector('input[name="shoot_date"]');
      if (shootDateInput) {
        const selectedDate = new Date(shootDateInput.value);
        const today = new Date();
        today.setHours(0,0,0,0);
        if (selectedDate < today) {
          alert('Please select a shoot date in the future.');
          return false;
        }
      }
    }

    // Step 2: Equipment selection
    if (step === 2) {
      const selectedServices = activePanel.querySelectorAll('input[name="selected_services[]"]:checked');
      if (selectedServices.length === 0) {
        alert('Please select at least one shooting service (Camera, Mobile, or Drone).');
        return false;
      }
    }

    // Step 3: Deliverable selection
    if (step === 3) {
      const deliverableSelected = activePanel.querySelector('input[name="deliverable_type"]:checked');
      if (!deliverableSelected) {
        alert('Please choose what you want the creator to capture (Photos, Video, or Both).');
        return false;
      }
    }

    return true;
  }

  function calculateBookingFinancials() {
    const startingPriceEl = document.getElementById('calc-starting-price');
    const advanceRateEl = document.getElementById('calc-advance-percent');
    if (!startingPriceEl) return;

    const baseStartingPrice = parseFloat(startingPriceEl.value || 0);
    const advancePercent = parseFloat(advanceRateEl.value || 20);

    // Calculate equipment extra charges
    let equipmentAddons = 0;
    const checkedServices = document.querySelectorAll('input[name="selected_services[]"]:checked');
    const serviceTitles = [];

    checkedServices.forEach(function (chk, idx) {
      const price = parseFloat(chk.getAttribute('data-price') || 0);
      const title = chk.getAttribute('data-title') || chk.value;
      serviceTitles.push(title);
      // First service uses starting price, additional services add incremental cost
      if (idx > 0) {
        equipmentAddons += (price * 0.75); // 25% bundled discount on 2nd+ equipment
      }
    });

    // Duration calculation
    const durationInput = document.getElementById('booking_duration_hours');
    const hours = durationInput ? parseInt(durationInput.value, 10) || 2 : 2;
    let extraDurationCharge = 0;
    if (hours > 2) {
      extraDurationCharge = (hours - 2) * (baseStartingPrice * 0.35);
    }

    const totalEstimate = baseStartingPrice + equipmentAddons + extraDurationCharge;
    const advanceAmount = (totalEstimate * (advancePercent / 100));
    const balanceAmount = totalEstimate - advanceAmount;

    // Update DOM Display Elements
    updateText('summary-display-equipment', serviceTitles.join(' + ') || 'Standard Shoot');
    updateText('summary-display-duration', hours + ' Hours');
    updateText('summary-display-base', formatINR(baseStartingPrice));
    updateText('summary-display-addons', formatINR(equipmentAddons));
    updateText('summary-display-extra-duration', formatINR(extraDurationCharge));
    updateText('summary-display-total', formatINR(totalEstimate));
    updateText('summary-display-advance', formatINR(advanceAmount));
    updateText('summary-display-balance', formatINR(balanceAmount));

    // Update Hidden Form Fields for submission
    setInputValue('input_calculated_equipment_charges', equipmentAddons.toFixed(2));
    setInputValue('input_calculated_duration_charges', extraDurationCharge.toFixed(2));
    setInputValue('input_calculated_total', totalEstimate.toFixed(2));
    setInputValue('input_calculated_advance', advanceAmount.toFixed(2));
    setInputValue('input_calculated_balance', balanceAmount.toFixed(2));

    // Update payment tab displays
    updateText('pay-advance-label-upi', formatINR(advanceAmount));
    updateText('pay-advance-label-card', formatINR(advanceAmount));
  }

  function updateText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  function setInputValue(name, val) {
    const el = document.querySelector(`input[name="${name}"]`);
    if (el) el.value = val;
  }

  function formatINR(num) {
    return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Initial show
  showStep(1);
}

/* -------------------------------------------------------------
   Modal Handlers
------------------------------------------------------------- */
function initModals() {
  document.querySelectorAll('[data-modal-target]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-modal-target');
      const modal = document.getElementById(targetId);
      if (modal) {
        modal.classList.add('open');
      }
    });
  });

  document.querySelectorAll('.modal-close, .modal-overlay').forEach(function (closeEl) {
    closeEl.addEventListener('click', function (e) {
      if (e.target === this || this.classList.contains('modal-close')) {
        const modal = this.closest('.modal-overlay');
        if (modal) {
          modal.classList.remove('open');
        }
      }
    });
  });
}

/* -------------------------------------------------------------
   Image Preview for Uploads
------------------------------------------------------------- */
function initImagePreviews() {
  const imageInputs = document.querySelectorAll('input[type="file"][data-preview]');
  imageInputs.forEach(function (input) {
    input.addEventListener('change', function () {
      const previewId = this.getAttribute('data-preview');
      const previewEl = document.getElementById(previewId);
      if (previewEl && this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          previewEl.src = e.target.result;
          previewEl.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });
}
