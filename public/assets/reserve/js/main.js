// show steps

const allSteps = document.querySelectorAll("form .step");
const thankStep = document.querySelector(".thanks-step");
const nextBtn = document.getElementById("next");
const backBtn = document.getElementById("back");
const stepNums = document.querySelectorAll("aside .sidebar .icon");
const changePlanBtn = document.querySelector("#changePlan");
const addonss = document.querySelectorAll(".addons .addon");
const addonChecks = document.querySelectorAll(".addons .form-check-input");
const planName = document.querySelector(".plan-name");
const planPriceSp = document.querySelector(".plan-price");
const monthPlan = document.querySelector("#month-plan");
const yearPlan = document.querySelector("#year-plan");
const monthlyAddons = document.querySelector("#monthly-addons");
const yearlyAddons = document.querySelector("#yearly-addons");
const checkoutForm = document.getElementById("checkoutForm");
const mplanCards = document.querySelectorAll("#month-plan .plan");
const yplanCards = document.querySelectorAll("#year-plan .plan");
const monthlyPlanBtns = document.querySelectorAll("#month-plan .plan-type");
const yearlyPlanBtns = document.querySelectorAll("#year-plan .plan-type");
const darkModeBtn = document.querySelector("#darkmode");
const pricingTables = document.getElementsByClassName("table");
console.log()
const themeMode = document.querySelector(".theme-mode");

//checker
document.addEventListener("DOMContentLoaded", function () {
  const mediaState = window.matchMedia("(prefers-color-scheme:dark)");

  mediaState.matches ? darkMode() : window.localStorage.getItem("theme") === "dark" ? darkMode() : lightMode();
});

//Btn
darkModeBtn.addEventListener("change", toggleDarkMode);

//apply darkMode
function toggleDarkMode() {
  darkModeBtn.checked ? darkMode() : lightMode();
}
function darkMode() {
  themeMode.classList.add("dark-on");
  document.body.classList.add("dark-mode");

  // Add dark mode class to all tables
  Array.from(pricingTables).forEach((table) => {
      table.classList.add("table-dark");
  });

  window.localStorage.setItem("theme", "dark");
}

function lightMode() {
  themeMode.classList.remove("dark-on");
  document.body.classList.remove("dark-mode");

  // Remove dark mode class from all tables
  Array.from(pricingTables).forEach((table) => {
      table.classList.remove("table-dark");
  });

  window.localStorage.setItem("theme", "light");
}


//vars
let currentStep = 0;
// const addonPlanPrices = new Map();
// const addonsPrices = [];
// const planPrices = {
//   arcade: { name: arcade, month: 9, year: 100 },
//   advanced: { name: advanced, month: 12, year: 120 },
//   pro: { name: pro, month: 20, year: 150 },
// };
// const addonData = {
//   onlineservice: { name: "online service", month: 1, year: 10 },
//   largestorage: { name: "large storage", month: 2, year: 20 },
//   customprofile: { name: "custom profile", month: 1, year: 20 },
// };

changePlanBtn.addEventListener("click", function () {
  currentStep = 1;
  updateStep();
  // document.querySelector(".summary-step .total").remove();
});


//Run
updateStep();
choosePlan();
// chooseAddons();

//form steps
function updateStep() {
  stepNums.forEach((stepNum) => {
    stepNum.classList.remove("checked");
  });
  stepNums[currentStep].classList.add("checked");


  allSteps.forEach((step) => {
    step.classList.add("d-none");
  });
  allSteps[currentStep].classList.remove("d-none");

  if (currentStep == 0) {
    backBtn.classList.add("d-none");
  } else {
    backBtn.classList.remove("d-none");
  }

  if (currentStep == allSteps.length - 1) {
    nextBtn.innerHTML = "Confirm";
    // totalCalc();
    nextBtn.classList.add("done");

  } else {
    nextBtn.innerHTML = "Next";
    nextBtn.classList.remove("done");

  }
}

nextBtn.addEventListener("click", nextStep);
backBtn.addEventListener("click", backStep);

function nextStep(e) {

  // if (currentStep == 0 || currentStep == 1) {
  if (!formChecker()) {
    return 0;
  }
  // }
  if (nextBtn.classList.contains("done")) {
    // nextBtn.type = "submit"
    // checkoutForm.addEventListener('submit', (e) => {

    //   e.preventDefault()
    // })
    checkoutForm.classList.add("d-none");
    thankStep.classList.remove("d-none");
  }
  else {
    currentStep++;
    updateStep();
  }

}

function backStep() {
  if (currentStep == 3) {
    // document.querySelector(".summary-step .total").remove();
    currentStep--;
    updateStep();
  } else {
    currentStep--;
    updateStep();
  }
}

// remove any addon if plan changed
function removeIfChange() {
  let addonDiv = document.querySelectorAll(".summary-step .addon");
  if (addonDiv) {
    addonDiv.forEach((addon) => {
      removeAddon(addon.id);
    });
  }
}

//choose plan
function choosePlan(btns = monthlyPlanBtns, plans = mplanCards) {
  // انتخاب تمام دکمه‌های رادیو و والدین آن‌ها

  // افزودن رویداد به هر دکمه
  btns.forEach((btn, ind) => {
    btn.addEventListener("change", function () {
      // حذف کلاس checked از تمام آیتم‌ها
      plans.forEach((plan) => {
        plan.classList.remove("checked");
      });

      // افزودن کلاس checked به آیتم انتخاب‌شده
      if (btn.checked) {
        plans[ind].classList.add("checked");
      }
    });
  });

}

//add plan to summary
function addPlan(btnValue) {
  let [dur, type] = btnValue.split("-");
  let period = dur == "month" ? "Month" : "Year";
  let planPrice = planPrices[type][dur];
  planName.innerHTML = `${type} (${period}ly)`;
  planPriceSp.innerHTML = `+$${planPrice}/${period}`;
  addonPlanPrices.set("planType", period);
  addonPlanPrices.set("planPrice", planPrice);
}

// choose add-on services
// function chooseAddons() {

//   addonChecks.forEach((check) => {
//     check.addEventListener("click", function () {
//       if (check.checked) {
//         addons(check);
//         check.parentElement.parentElement.classList.add("checkAddon");
//       } else {
//         check.parentElement.parentElement.classList.remove("checkAddon");
//         removeAddon(check.value);
//       }
//     });
//   });
//   //   addonss.forEach((addon) => {
//   //   addon.addEventListener("click", function () {
//   //     const addonChecked = addon.querySelector(".form-check-input");
//   //     if (addonChecked.checked) {
//   //       addonChecked.checked = false;
//   //       this.classList.remove("checkAddon");
//   //       removeAddon(addonChecked.value);
//   //     } else {
//   //       addonChecked.checked = true;
//   //       this.classList.add("checkAddon");
//   //       addons(addonChecked);
//   //     }
//   //   });
//   // });
// }

// // add Addons to summary
// function addons(check) {
//   let [dur, type] = check.value.split("-");
//   let addonName = type.toLowerCase();
//   let addonDur = dur == "month" ? "Month" : "Year";
//   let addonPrice = addonData[addonName][dur];
//   let addonNamePrice = {};
//   addonNamePrice.addonName = addonName;
//   addonNamePrice.addonPrice = addonPrice;
//   addonNamePrice.addonDur = addonDur;
//   addonsPrices.push(addonNamePrice);
//   // update Prices Map
//   addonPlanPrices.set("addons", addonsPrices);

//   const summaryAddonDiv = document.querySelector(".summary-step .summary");
//   const addonDiv = document.createElement("div");
//   const addonNameSp = document.createElement("span");
//   const addonPriceSp = document.createElement("span");
//   addonDiv.classList.add("addon", "p-3", "mb-2", "mb-md-2", "d-flex", "align-items-center");
//   addonDiv.id = check.value;

//   addonNameSp.classList.add("me-auto", "addon-name");
//   addonPriceSp.classList.add("ms-auto", "addon-price");
//   addonNameSp.innerHTML = addonData[addonName]["name"];
//   addonPriceSp.innerHTML = `+$${addonPrice}/${addonDur}`;
//   addonDiv.append(addonNameSp, addonPriceSp);
//   summaryAddonDiv.append(addonDiv);
// }
// function removeAddon(id) {

//   document.getElementById(id).remove();

//   let [, name] = id.split("-");
//   let addonName = name.toLowerCase();

//   addonPlanPrices.get("addons").forEach((element, ind, arr) => {
//     if (element.addonName === addonName) {
//       arr.splice(ind, 1);
//     }
//   });
// }

// function totalCalc() {

//   const totalSummary = document.querySelector(".summary-step");
//   const totalDiv = document.createElement("div");
//   totalDiv.classList.add("total", "p-3", "mb-2", "mb-md-3", "d-flex", "align-items-center");
//   const totalDur = document.createElement("span");
//   const totalPrice = document.createElement("span");
//   totalDur.classList.add("me-auto", "total-dur");
//   totalPrice.classList.add("ms-auto", "total-price");
//   totalDur.innerHTML = `Total Per (${addonPlanPrices.get("planType")})`;
//   totalPrice.innerHTML = doCalc();
//   totalDiv.append(totalDur, totalPrice);
//   totalSummary.append(totalDiv);
// }

// function doCalc() {
//   let planPrice = addonPlanPrices.get("planPrice");
//   let addonsPrices = 0;

//   if (addonPlanPrices.has("addons")) {
//     addonsPrices = addonPlanPrices.get("addons").reduce((sum, curr) => {
//       return sum + curr.addonPrice;
//     }, 0);
//   }
//   return `${planPrice + addonsPrices}/${addonPlanPrices.get("planType")}`;
// }

// Disabling form submissions if there are invalid fields
function formChecker() {
  let valid = true;
  if (currentStep == 0) {
    let email = checkoutForm.email;
    let firstName = checkoutForm.first_name;
    let lastName = checkoutForm.last_name;
    let phone = checkoutForm.phone;
    let messengerPhone = checkoutForm.messenger_phone;


    // Validate First Name
    if (!isValidName(firstName)) {
      valid = true;
    }

    // Validate Last Name
    if (!isValidName(lastName)) {
      valid = true;
    }

    // Validate Email
    if (!isValidEmail(email)) {
      valid = true;
    }

    // Validate Phone
    if (!isValidPhone(phone)) {
      valid = true;
    }

    if (!isValidMessengerPhone(messengerPhone)) {
      valid = true;
    }


    return valid;
  }

  if (currentStep == 1) {
    let returnDate = checkoutForm.return_date;
    let pickupDate = checkoutForm.pickup_date;

    // Validate Return Date is after Pickup Date
    if (!isReturnDateAfterPickupDate(pickupDate, returnDate)) {
      valid = true;
    }
    // Validate Pickup Date
    if (!isValidDate(pickupDate)) {
      valid = true;
    }

    if (!isValidDate(returnDate)) {
      valid = true;
    }

    if (!validatePlans()) {
      valid = true;
    }
    return valid;
  }

  if (currentStep == 2) {
    return valid;
  }
}

function isValidName(nameField) {
  let validName = true;
  let re = /\w{3,}/ig;
  if (!re.test(nameField.value)) {
    validName = false;
    nameField.classList.remove("is-valid");
    nameField.classList.add("is-invalid");
  } else {
    nameField.classList.remove("is-invalid");
    nameField.classList.add("is-valid");
  }
  return validName;
}

function isValidEmail(email) {
  let validEmail = true;
  let re = /[a-zA-Z0-9]+@[a-zA-Z0-9]+.\w{1,}/ig;
  if (!re.test(email.value)) {
    validEmail = false;
    email.classList.remove("is-valid");
    email.classList.add("is-invalid");
  }
  else {
    email.classList.remove("is-invalid");
    email.classList.add("is-valid");
  }
  return validEmail
}

function isValidPhone(phoneField) {
  let validPhone = true;
  let re = /^09\d{9}$/; // Regex baraye shomare haye Iran
  let feedbackElement = phoneField.nextElementSibling; // Element ba class 'invalid-feedback'

  if (!re.test(phoneField.value)) {
    validPhone = false;
    phoneField.classList.remove("is-valid");
    phoneField.classList.add("is-invalid");

    // Update message bar asas khata
    if (phoneField.value.length < 11) {
      feedbackElement.textContent = "Phone number must be 11 digits!";
    } else if (!phoneField.value.startsWith("09")) {
      feedbackElement.textContent = "Phone number must start with '09'.";
    } else {
      feedbackElement.textContent = "Invalid phone number format.";
    }
  } else {
    phoneField.classList.remove("is-invalid");
    phoneField.classList.add("is-valid");
    feedbackElement.textContent = ""; // Clear error message
  }

  return validPhone;
}
function isValidMessengerPhone(phoneField) {
  let validMessengerPhone = true;
  let re = /^09\d{9}$/; // Regex baraye shomare haye Iran
  let feedbackElement = phoneField.nextElementSibling; // Element ba class 'invalid-feedback'

  if (!re.test(phoneField.value)) {
    validMessengerPhone = false;
    phoneField.classList.remove("is-valid");
    phoneField.classList.add("is-invalid");

    // Update message bar asas khata
    if (phoneField.value.length < 11) {
      feedbackElement.textContent = "Messenger number must be 11 digits!";
    } else if (!phoneField.value.startsWith("09")) {
      feedbackElement.textContent = "Messenger number must start with '09'.";
    } else {
      feedbackElement.textContent = "Invalid messenger number format.";
    }
  } else {
    phoneField.classList.remove("is-invalid");
    phoneField.classList.add("is-valid");
    feedbackElement.textContent = ""; // Clear error message
  }

  return validMessengerPhone;
}

function validatePlans(btns = monthlyPlanBtns) {
  let validPlan = true;
  let feedback = document.querySelector(".bad-feedback-plan");
  let checkedCount = 0;
  btns.forEach((inp) => {
    if (inp.checked) {
      checkedCount++;
    }
  })
  if (checkedCount == 0) {
    feedback.classList.remove("d-none")
    validPlan = false
  }
  else {
    feedback.classList.add("d-none")
  }
  return validPlan;
}

function isValidDate(dateField) {

  if (!dateField || !dateField.value || isNaN(new Date(dateField.value).getTime())) {
    dateField.classList.remove("is-valid");
    dateField.classList.add("is-invalid");
    return false;
  }
  dateField.classList.remove("is-invalid");
  dateField.classList.add("is-valid");
  return true;
}

function isReturnDateAfterPickupDate(pickupField, returnField) {
  let pickupDate = new Date(pickupField.value);
  let returnDate = new Date(returnField.value);
  let errorDiv = document.getElementById("date-error");

  // Moghayese be timestamp baraye asalat
  if (pickupDate.getTime() > returnDate.getTime()) {
      returnField.classList.remove("is-valid");
      returnField.classList.add("is-invalid");
      errorDiv.classList.remove("d-none"); // Show error message
      return false;
  } else {
      returnField.classList.remove("is-invalid");
      returnField.classList.add("is-valid");
      errorDiv.classList.add("d-none"); // Hide error message
      return true;
  }
}



