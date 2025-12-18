/**
 * ChillGC Tierlist - Main JavaScript
 * Handles drag-and-drop ranking functionality
 */

class TierlistApp {
    constructor() {
        this.people = [];
        this.rankings = {};
        this.draggedElement = null;
        
        this.init();
    }
    
    async init() {
        await this.loadData();
        this.render();
        this.setupDragAndDrop();
        this.setupAddPersonForm();
    }
    
    async loadData() {
        try {
            // Load people and rankings in parallel
            const [peopleRes, rankingsRes] = await Promise.all([
                fetch('/api/people.php'),
                fetch('/api/rankings.php')
            ]);
            
            const peopleData = await peopleRes.json();
            const rankingsData = await rankingsRes.json();
            
            this.people = peopleData.people || [];
            this.rankings = rankingsData.rankings || {};
        } catch (error) {
            console.error('Failed to load data:', error);
            this.showToast('Failed to load data', 'error');
        }
    }
    
    render() {
        const tiers = ['S', 'A', 'B', 'C', 'D', 'F'];
        
        // Organize people by tier
        const tierPeople = {
            'S': [], 'A': [], 'B': [], 'C': [], 'D': [], 'F': [], 'unranked': []
        };
        
        this.people.forEach(person => {
            const tier = this.rankings[person.id];
            if (tier && tierPeople[tier]) {
                tierPeople[tier].push(person);
            } else {
                tierPeople.unranked.push(person);
            }
        });
        
        // Render tier rows
        tiers.forEach(tier => {
            const content = document.querySelector(`.tier-row[data-tier="${tier}"] .tier-content`);
            if (content) {
                content.innerHTML = tierPeople[tier].length ? 
                    tierPeople[tier].map(p => this.createPersonCard(p)).join('') :
                    '';
            }
        });
        
        // Render unranked pool
        const unrankedPool = document.getElementById('unranked-pool');
        if (unrankedPool) {
            if (tierPeople.unranked.length) {
                unrankedPool.innerHTML = tierPeople.unranked.map(p => this.createPersonCard(p)).join('');
            } else {
                unrankedPool.innerHTML = '<div class="empty-message">All members have been ranked! 🎉</div>';
            }
        }
    }
    
    createPersonCard(person) {
        const avatar = person.avatar_url || `https://cdn.discordapp.com/embed/avatars/${(person.id || 0) % 5}.png`;
        return `
            <div class="person-card" draggable="true" data-person-id="${person.id}">
                <img class="person-avatar" src="${this.escapeHtml(avatar)}" alt="${this.escapeHtml(person.name)}" 
                     onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                <span class="person-name">${this.escapeHtml(person.name)}</span>
            </div>
        `;
    }
    
    setupDragAndDrop() {
        // Use event delegation for drag events
        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('person-card')) {
                this.draggedElement = e.target;
                this.draggedPersonId = e.target.dataset.personId;
                e.target.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', e.target.dataset.personId);
            }
        });
        
        document.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('person-card')) {
                e.target.classList.remove('dragging');
                this.draggedElement = null;
                this.draggedPersonId = null;
                
                // Remove all drag-over classes
                document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            }
        });
        
        // Setup drop zones using event delegation on document
        document.addEventListener('dragover', (e) => {
            const zone = e.target.closest('.tier-content, #unranked-pool');
            if (zone) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const container = zone.closest('.tier-row') || zone;
                container.classList.add('drag-over');
            }
        });
        
        document.addEventListener('dragleave', (e) => {
            const zone = e.target.closest('.tier-content, #unranked-pool');
            if (zone && !zone.contains(e.relatedTarget)) {
                const container = zone.closest('.tier-row') || zone;
                container.classList.remove('drag-over');
            }
        });
        
        document.addEventListener('drop', async (e) => {
            const zone = e.target.closest('.tier-content, #unranked-pool');
            if (!zone) return;
            
            e.preventDefault();
            const container = zone.closest('.tier-row') || zone;
            container.classList.remove('drag-over');
            
            // Use stored personId (more reliable than dataTransfer in some browsers)
            const personId = this.draggedPersonId || e.dataTransfer.getData('text/plain');
            if (!personId) {
                console.error('No person ID found for drop');
                return;
            }
            
            // Determine target tier
            const tierRow = zone.closest('.tier-row');
            const tier = tierRow ? tierRow.dataset.tier : null;
            
            console.log('Dropping person', personId, 'to tier', tier);
            await this.updateRanking(parseInt(personId), tier);
        });
    }
    
    async updateRanking(personId, tier) {
        // Check for self-voting
        if (tier && window.CURRENT_USER_DISCORD_ID) {
            const person = this.people.find(p => p.id == personId);
            if (person && person.discord_id && String(person.discord_id) === String(window.CURRENT_USER_DISCORD_ID)) {
                this.showToast("You can't rank yourself! 🙄", 'error');
                return;
            }
        }
        
        try {
            if (tier) {
                // Save ranking
                const response = await fetch('/api/rankings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ person_id: personId, tier: tier })
                });
                
                if (!response.ok) throw new Error('Failed to save');
                
                this.rankings[personId] = tier;
                this.showToast(`Ranked in ${tier} tier!`, 'success');
            } else {
                // Remove ranking (moved to unranked)
                const response = await fetch('/api/rankings.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ person_id: personId })
                });
                
                if (!response.ok) throw new Error('Failed to remove');
                
                delete this.rankings[personId];
                this.showToast('Moved to unranked', 'success');
            }
            
            this.render();
        } catch (error) {
            console.error('Failed to update ranking:', error);
            this.showToast('Failed to save ranking', 'error');
        }
    }
    
    setupAddPersonForm() {
        const form = document.getElementById('add-person-form');
        if (!form) return;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const input = form.querySelector('input[name="discord_id"]');
            const discordId = input?.value.trim();
            
            if (!discordId) {
                this.showToast('Please enter a Discord User ID', 'error');
                return;
            }
            
            // Validate it looks like a Discord ID (17-20 digit number)
            if (!/^\d{17,20}$/.test(discordId)) {
                this.showToast('Invalid Discord ID format', 'error');
                return;
            }
            
            try {
                const response = await fetch('/api/people.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ discord_id: discordId })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Failed to add person');
                }
                
                this.people.push(data.person);
                this.render();
                input.value = '';
                this.showToast(`Added ${data.person.name}!`, 'success');
            } catch (error) {
                console.error('Failed to add person:', error);
                this.showToast(error.message, 'error');
            }
        });
    }
    
    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.tierlistApp = new TierlistApp();
});
