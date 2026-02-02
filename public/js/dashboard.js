// Toast Notification System
function showToast(message, type = 'info') {
    // Create container if not exists
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Create Toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';

    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <div>${message}</div>
    `;

    container.appendChild(toast);

    // Animate In
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    // Remove after 3s
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

document.addEventListener('DOMContentLoaded', function () {

    // 1. AI Financial Advisor Widget (Common Profile)
    // Only runs if the element exists
    const aiContent = document.getElementById('ai-content');
    if (aiContent) {
        // Simulated delay for "Thinking" effect
        setTimeout(() => {
            fetchAIAnalysis();
        }, 1500);
    }

    // 2. File Upload Listeners (Generic)
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                showToast(`Arquivo "${this.files[0].name}" selecionado com sucesso.`, 'info');
            }
        });
    });

    // Animation: Staggered Fade In for Cards
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });

    function fetchAIAnalysis() {
        // Fetch from API Mock
        fetch('/financeiro/public/ai/analyze')
            .then(response => response.json())
            .then(data => {
                const aiDiv = document.getElementById('ai-content');

                // Randomize tips for "AI feel"
                const tips = [
                    "Seu perfil de gastos indica uma economia potencial de <strong>R$ 200,00</strong> em transporte.",
                    "Atenção: Você atingiu 80% do seu orçamento de lazer este mês.",
                    "Parabéns! Sua reserva de emergência cresceu 5% no último trimestre.",
                    "Dica: Investir seu saldo parado poderia render R$ 50,00 extras este mês."
                ];
                const randomTip = tips[Math.floor(Math.random() * tips.length)];

                aiDiv.innerHTML = `
                    <div class="fade-in-up" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; font-size: 0.9rem;">
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                            <i class="fas fa-lightbulb" style="color: #FFC107; margin-top:3px;"></i> 
                            <div>
                                <strong>Insight FinAI:</strong><br>
                                ${randomTip}
                            </div>
                        </div>
                    </div>
                `;

                // Optional Toast
                // showToast('FinAI encontrou um novo insight!', 'info');
            })
            .catch(error => console.error('Error fetching AI analysis:', error));
    }
});
