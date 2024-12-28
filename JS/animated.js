document.addEventListener("DOMContentLoaded", () => {
    const texts = document.querySelectorAll(".animated-text");

    texts.forEach((text) => {
        const fullText = text.getAttribute("data-text");
        text.textContent = "";

        let charIndex = 0;

        const typeInterval = setInterval(() => {
            if (charIndex < fullText.length) {
                text.textContent += fullText[charIndex];
                charIndex++;
            } else {
                clearInterval(typeInterval); // Përfundimi i shkrimit
            }
        }, 100); // Shpejtësia e shkrimit (100ms për çdo shkronjë)
    });
});
