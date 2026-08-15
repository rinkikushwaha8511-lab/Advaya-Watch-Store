/**
 * Advaya Watch Store - Main Client Script
 * 
 * Elegant, vanilla JS interaction.
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('✨ Advaya Watch Store - Premium UI initialized.');
    
    // Add micro-interaction or transition effects here as features are built
    const cards = document.querySelectorAll('.product-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * (index + 1));
    });
});
