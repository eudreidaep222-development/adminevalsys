// WAIT FOR DOCUMENT READY
document.addEventListener("DOMContentLoaded", function () {
  var successMessage = document.getElementById("successMessage");
  var failureMessage = document.getElementById("failureMessage");

  // IF SUCCESS MESSAGE IS PRESENT, FADE IT OUT AND REDIRECT
  if (successMessage) {
    setTimeout(function () {
      fadeOutAndRedirect(successMessage);
    }, 5000);
  }

  // IF FAILURE MESSAGE IS PRESENT, FADE IT OUT AND REDIRECT
  if (failureMessage) {
    setTimeout(function () {
      fadeOutAndRedirect(failureMessage);
    }, 5000);
  }
});

document
  .getElementById("forgotPasswordBtn")
  .addEventListener("click", function () {
    document.getElementById("forgotPasswordModal").style.display = "block";
    setTimeout(function () {
      document.getElementById("forgotPasswordModal").style.opacity = "1";
    }, 100); // Delay to allow time for the display property to take effect
  });

document.querySelector(".close").addEventListener("click", function () {
  document.getElementById("forgotPasswordModal").style.opacity = "0";
  setTimeout(function () {
    document.getElementById("forgotPasswordModal").style.display = "none";
  }, 300); // Duration of the fade-out transition
});

function showTermsPopup() {
  const popup = document.getElementById("termsPopup");
  popup.style.display = "block";
}

// Event listener for the checkbox
document
  .getElementById("termsCheckbox")
  .addEventListener("change", function () {
    if (this.checked) {
      showTermsPopup();
    }
  });

function acceptTerms() {
  document.getElementById("termsCheckbox").checked = true;
  document.querySelector(".forms_buttons-action").disabled = false;
  document.getElementById("termsPopup").style.display = "none";
}

function declineTerms() {
  document.getElementById("termsCheckbox").checked = false;
  document.getElementById("termsPopup").style.display = "none";
}
function showTermsPopup() {
  const popup = document.getElementById("termsPopup");
  popup.style.display = "block";
}

function scrollToTop() {
  // Scroll to the top of the terms and conditions pop-up smoothly
  const popup = document.getElementById("termsPopup");
  popup.scrollTo({ top: 0, behavior: "smooth" });
}

function scrollToButtons() {
  // Scroll to the terms and conditions buttons section
  const buttonsSection = document.getElementById("termsButtons");
  buttonsSection.scrollIntoView({ behavior: "smooth" });

  // Trigger bounce animation on terms-popup-content
  const popupContent = document.querySelector(".terms-popup-content");
  popupContent.classList.add("bounceInUp");
  setTimeout(() => {
    popupContent.classList.remove("bounceInUp");
  }, 1000); // Adjust the duration to match your animation
}

// function scrollToButtons() {
//   // Scroll to the terms and conditions buttons section
//   const buttonsSection = document.getElementById('termsButtons');
//   buttonsSection.scrollIntoView({ behavior: 'smooth' });
// }

// function scrollToTop() {
//   // Scroll to the top of the terms and conditions pop-up smoothly
//   const popup = document.getElementById('termsPopup');
//   popup.scrollTo({ top: 0, behavior: 'smooth' });

//   // Trigger bounce animation on terms-popup-content
//   const popupContent = document.querySelector('.terms-popup-content');
//   popupContent.classList.add('bounceInUp');
//   setTimeout(() => {
//     popupContent.classList.remove('bounceInUp');
//   }, 10); // Adjust the duration to match your animation
// }

function scrollToButtons() {
  // Scroll to the terms and conditions buttons section
  const buttonsSection = document.getElementById("termsButtons");
  buttonsSection.scrollIntoView({ behavior: "smooth" });

  // Trigger bounce animation on terms-popup-content
  const popupContent = document.querySelector(".terms-popup-content");
  popupContent.classList.add("bounceInUp");
  setTimeout(() => {
    popupContent.classList.remove("bounceInUp");
  }, 1000); // Adjust the duration to match your animation
}
