<?php
/**
 * Enhanced quiz interface with question number navigation
 * Insert this code into the questions container section of quiz_new.php
 */
?>

<!-- Question Navigation Panel -->
<div class="sticky top-24 z-40 bg-white shadow-lg rounded-xl p-4 mb-6 border-t-4 border-purple-600">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-bold text-gray-700">
            <i class='bx bx-list-check mr-2'></i>Question Navigator
        </h3>
        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full font-semibold">
            <span id="navProgress">0</span> / <?php echo $count; ?>
        </span>
    </div>
    
    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-2">
        <?php for ($i = 1; $i <= count($questions); $i++): ?>
        <button 
            onclick="goToQuestion(<?php echo $i; ?>)" 
            class="question-nav-btn nav-btn-<?php echo $i; ?> h-10 w-10 rounded-lg font-bold text-sm transition-all duration-200 border-2 border-gray-300 bg-white text-gray-700 hover:border-purple-500 hover:bg-purple-50"
            data-question="<?php echo $i; ?>"
            title="Question <?php echo $i; ?>">
            <?php echo $i; ?>
        </button>
        <?php endfor; ?>
    </div>
    
    <div class="mt-3 flex items-center justify-between text-xs text-gray-600">
        <div class="flex gap-4">
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 bg-green-400 rounded-sm"></span>
                <span>Answered</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 bg-gray-300 rounded-sm"></span>
                <span>Unanswered</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 bg-purple-400 rounded-sm"></span>
                <span>Current</span>
            </div>
        </div>
    </div>
</div>

<style>
    .question-nav-btn.answered {
        background: #10b981;
        border-color: #059669;
        color: white;
    }
    
    .question-nav-btn.current {
        background: #7c3aed;
        border-color: #6d28d9;
        color: white;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
    }
    
    .question-nav-btn.unanswered {
        background: #e5e7eb;
        border-color: #d1d5db;
        color: #374151;
    }
</style>

<script>
let currentQuestion = 1;

function goToQuestion(qNum) {
    // Update visual indicator
    document.querySelectorAll('.question-nav-btn').forEach(btn => {
        btn.classList.remove('current');
    });
    document.querySelector(`.nav-btn-${qNum}`).classList.add('current');
    
    // Scroll to question
    const question = document.querySelector(`.question-card[data-qid="${questionIds[qNum-1]}"]`);
    if (question) {
        question.scrollIntoView({ behavior: 'smooth', block: 'start' });
        question.classList.add('highlight');
        setTimeout(() => question.classList.remove('highlight'), 1500);
    }
    
    currentQuestion = qNum;
}

function updateNavigatorButtons() {
    answeredQuestions.forEach(qid => {
        const qIndex = questionIds.indexOf(qid);
        if (qIndex !== -1) {
            const btn = document.querySelector(`.nav-btn-${qIndex + 1}`);
            if (btn) {
                btn.classList.remove('unanswered');
                btn.classList.add('answered');
            }
        }
    });
    
    document.getElementById('navProgress').textContent = answeredQuestions.size;
}

// Update question navigator whenever an answer changes
const originalUpdateProgress = updateProgress;
updateProgress = function(qid) {
    originalUpdateProgress(qid);
    updateNavigatorButtons();
};

// Initialize first question as current
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        document.querySelector('.nav-btn-1').classList.add('current');
        document.querySelectorAll('.question-nav-btn').forEach(btn => {
            btn.classList.add('unanswered');
        });
    }, 500);
});

// Add highlight animation
const style = document.createElement('style');
style.textContent = `
    @keyframes highlight-pulse {
        0% { background-color: rgba(124, 58, 237, 0.2); }
        100% { background-color: rgba(124, 58, 237, 0); }
    }
    
    .question-card.highlight {
        animation: highlight-pulse 1.5s ease-out;
        border-left-width: 6px !important;
    }
`;
document.head.appendChild(style);
</script>
