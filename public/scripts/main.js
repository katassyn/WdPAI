const menuIcon = document.querySelector(".nav-toggle");
const navList = document.querySelector("nav > div.container > ul");

menuIcon.addEventListener("click", () => {
    if (navList.style.display === "block") {
        navList.style.display = "";
    } else {
        navList.style.display = "block";
    }
});