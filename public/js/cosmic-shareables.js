/**
 * Cosmic Shareables - Animated shareable generator
 * Creates beautiful animated cosmic reports for social sharing
 */

class CosmicShareables {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.animationId = null;
        this.startTime = null;
        this.config = {}; // Initialize with an empty object or default production config as needed
    }

    /**
     * Initialize canvas and create animated shareable
     */
    async generateShareable(containerId, type, data) {
        try {
            // Create canvas element
            const container = document.getElementById(containerId);
            if (!container) {
                throw new Error('Container not found');
            }

            this.canvas = document.createElement('canvas');
            this.canvas.width = 800;
            this.canvas.height = 800;
            this.canvas.style.maxWidth = '100%';
            this.canvas.style.height = 'auto';
            this.canvas.style.borderRadius = '12px';
            this.canvas.style.boxShadow = '0 10px 30px rgba(0,0,0,0.3)';
            
            container.innerHTML = '';
            container.appendChild(this.canvas);
            
            this.ctx = this.canvas.getContext('2d');
            
            // Get animation config from server
        // Ensure this endpoint and logic are production-ready and don't expose sensitive info.
            const response = await fetch(`/shareables/generate-${type}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Failed to generate shareable');
            }
            
            this.config = result.animationConfig;
                // console.log('Animation config loaded:', this.config); // Example of a console.log to remove/comment
            this.startAnimation();
            
            return {
                canvas: this.canvas,
                downloadUrl: this.getDownloadUrl(),
                shareUrl: result.data.shareableUrl || window.location.href
            };
            
        } catch (error) {
            console.error('Error generating shareable:', error);
            throw error;
        }
    }

    /**
     * Start the animation loop
     */
    startAnimation() {
        this.startTime = Date.now();
        this.animate();
    }

    /**
     * Main animation loop
     */
    animate() {
        const currentTime = Date.now();
        const elapsed = currentTime - this.startTime;
        const progress = Math.min(elapsed / this.config.duration, 1);

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Draw background
        this.drawBackground(progress);

        // Draw particles
        if (this.config.particles) {
            this.drawParticles(progress);
        }

        // Draw elements
        if (this.config.elements) {
            this.config.elements.forEach(element => {
                this.drawElement(element, progress);
            });
        }

        // Continue animation or loop
        if (progress < 1) {
            this.animationId = requestAnimationFrame(() => this.animate());
        } else {
            // Loop the animation
            setTimeout(() => {
                this.startTime = Date.now();
                this.animate();
            }, 1000);
        }
    }

    /**
     * Draw animated background
     */
    drawBackground(progress) {
        const bg = this.config.background;
        
        if (bg.type === 'gradient') {
            const gradient = this.ctx.createLinearGradient(0, 0, this.canvas.width, this.canvas.height);
            
            if (bg.animation === 'pulse') {
                const intensity = 0.7 + 0.3 * Math.sin(progress * Math.PI * 4);
                gradient.addColorStop(0, this.adjustColorIntensity(bg.colors[0], intensity));
                gradient.addColorStop(1, this.adjustColorIntensity(bg.colors[1], intensity));
            } else {
                gradient.addColorStop(0, bg.colors[0]);
                gradient.addColorStop(1, bg.colors[1]);
            }
            
            this.ctx.fillStyle = gradient;
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        } else if (bg.type === 'cosmic_field') {
            this.drawCosmicField(progress, bg.rarity_level);
        }
    }

    /**
     * Draw cosmic field background
     */
    drawCosmicField(progress, rarityLevel) {
        const colors = this.getRarityColors(rarityLevel);
        const gradient = this.ctx.createRadialGradient(
            this.canvas.width / 2, this.canvas.height / 2, 0,
            this.canvas.width / 2, this.canvas.height / 2, this.canvas.width / 2
        );
        
        gradient.addColorStop(0, colors[0]);
        gradient.addColorStop(0.7, colors[1]);
        gradient.addColorStop(1, '#000011');
        
        this.ctx.fillStyle = gradient;
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }

    /**
     * Draw animated particles
     */
    drawParticles(progress) {
        const particles = this.config.particles;
        
        if (particles.type === 'stars') {
            for (let i = 0; i < particles.count; i++) {
                const x = (i * 137.5) % this.canvas.width;
                const y = (i * 73.3) % this.canvas.height;
                const twinkle = Math.sin(progress * Math.PI * 2 + i) * 0.5 + 0.5;
                
                this.ctx.fillStyle = `rgba(255, 255, 255, ${twinkle * 0.8})`;
                this.ctx.beginPath();
                this.ctx.arc(x, y, 1 + twinkle, 0, Math.PI * 2);
                this.ctx.fill();
            }
        }
    }

    /**
     * Draw individual animated elements
     */
    drawElement(element, progress) {
        const delay = (element.delay || 0) / this.config.duration;
        const elementProgress = Math.max(0, (progress - delay) / (1 - delay));
        
        if (elementProgress <= 0) return;

        this.ctx.save();
        
        switch (element.type) {
            case 'zodiac_symbol':
                this.drawZodiacSymbol(element, elementProgress);
                break;
            case 'text':
                this.drawAnimatedText(element, elementProgress);
                break;
            case 'heart':
                this.drawAnimatedHeart(element, elementProgress);
                break;
            case 'score_circle':
                this.drawScoreCircle(element, elementProgress);
                break;
            case 'rarity_gem':
                this.drawRarityGem(element, elementProgress);
                break;
            case 'score_counter':
                this.drawScoreCounter(element, elementProgress);
                break;
        }
        
        this.ctx.restore();
    }

    /**
     * Draw animated zodiac symbol
     */
    drawZodiacSymbol(element, progress) {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2 - 100;
        
        if (element.animation === 'rotate_glow') {
            const rotation = progress * Math.PI * 2;
            const glow = 0.5 + 0.5 * Math.sin(progress * Math.PI * 4);
            
            this.ctx.translate(centerX, centerY);
            this.ctx.rotate(rotation);
            
            // Glow effect
            this.ctx.shadowColor = '#FFD700';
            this.ctx.shadowBlur = 20 * glow;
            
            this.ctx.font = 'bold 120px serif';
            this.ctx.fillStyle = '#FFD700';
            this.ctx.textAlign = 'center';
            this.ctx.textBaseline = 'middle';
            this.ctx.fillText(element.symbol, 0, 0);
        }
    }

    /**
     * Draw animated text
     */
    drawAnimatedText(element, progress) {
        let alpha = 1;
        let y = this.getTextY(element.style);
        
        if (element.animation === 'fade_in') {
            alpha = this.easeInOut(progress);
        } else if (element.animation === 'slide_up') {
            alpha = this.easeInOut(progress);
            y += (1 - progress) * 50;
        } else if (element.animation === 'bounce') {
            alpha = 1;
            const bounce = Math.sin(progress * Math.PI * 6) * (1 - progress) * 10;
            y -= bounce;
        }
        
        this.ctx.globalAlpha = alpha;
        this.ctx.font = this.getTextFont(element.style);
        this.ctx.fillStyle = this.getTextColor(element.style);
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        
        this.ctx.fillText(element.content, this.canvas.width / 2, y);
    }

    /**
     * Draw animated heart
     */
    drawAnimatedHeart(element, progress) {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        const scale = 1 + element.intensity * Math.sin(progress * Math.PI * 8) * 0.2;
        
        this.ctx.translate(centerX, centerY);
        this.ctx.scale(scale, scale);
        
        // Draw heart shape
        this.ctx.fillStyle = '#FF6B9D';
        this.ctx.beginPath();
        this.ctx.moveTo(0, -20);
        this.ctx.bezierCurveTo(-25, -45, -75, -25, 0, 10);
        this.ctx.bezierCurveTo(75, -25, 25, -45, 0, -20);
        this.ctx.fill();
    }

    /**
     * Draw animated score circle
     */
    drawScoreCircle(element, progress) {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2 + 100;
        const radius = 80;
        const scoreProgress = this.easeInOut(progress);
        const angle = (element.value / 100) * Math.PI * 2 * scoreProgress;
        
        // Background circle
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
        this.ctx.lineWidth = 8;
        this.ctx.beginPath();
        this.ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
        this.ctx.stroke();
        
        // Progress circle
        this.ctx.strokeStyle = '#FF6B9D';
        this.ctx.lineWidth = 8;
        this.ctx.lineCap = 'round';
        this.ctx.beginPath();
        this.ctx.arc(centerX, centerY, radius, -Math.PI / 2, -Math.PI / 2 + angle);
        this.ctx.stroke();
        
        // Score text
        this.ctx.font = 'bold 36px Arial';
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText(Math.round(element.value * scoreProgress) + '%', centerX, centerY);
    }

    /**
     * Draw animated rarity gem
     */
    drawRarityGem(element, progress) {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        const rotation = progress * Math.PI * 2;
        const sparkle = Math.sin(progress * Math.PI * 8) * 0.5 + 0.5;
        
        this.ctx.translate(centerX, centerY);
        this.ctx.rotate(rotation);
        
        // Gem shape
        const colors = this.getRarityColors(element.rarity);
        this.ctx.fillStyle = colors[0];
        this.ctx.shadowColor = colors[1];
        this.ctx.shadowBlur = 20 * sparkle;
        
        this.ctx.beginPath();
        this.ctx.moveTo(0, -40);
        this.ctx.lineTo(30, -10);
        this.ctx.lineTo(20, 40);
        this.ctx.lineTo(-20, 40);
        this.ctx.lineTo(-30, -10);
        this.ctx.closePath();
        this.ctx.fill();
    }

    /**
     * Draw animated score counter
     */
    drawScoreCounter(element, progress) {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2 + 150;
        const currentScore = Math.round(element.target * this.easeInOut(progress));
        
        this.ctx.font = 'bold 48px Arial';
        this.ctx.fillStyle = '#FFD700';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText(currentScore + '%', centerX, centerY);
        
        this.ctx.font = '24px Arial';
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.fillText('RARITY SCORE', centerX, centerY + 40);
    }

    /**
     * Helper functions
     */
    easeInOut(t) {
        return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
    }

    adjustColorIntensity(color, intensity) {
        const hex = color.replace('#', '');
        const r = Math.round(parseInt(hex.substr(0, 2), 16) * intensity);
        const g = Math.round(parseInt(hex.substr(2, 2), 16) * intensity);
        const b = Math.round(parseInt(hex.substr(4, 2), 16) * intensity);
        return `rgb(${r}, ${g}, ${b})`;
    }

    getRarityColors(level) {
        const colorMap = {
            'Legendary': ['#FFD700', '#FFA500'],
            'Epic': ['#9B59B6', '#8E44AD'],
            'Rare': ['#3498DB', '#2980B9'],
            'Uncommon': ['#2ECC71', '#27AE60'],
            'Common': ['#95A5A6', '#7F8C8D']
        };
        return colorMap[level] || colorMap['Common'];
    }

    getTextY(style) {
        const positions = {
            'title': this.canvas.height / 2 + 50,
            'subtitle': this.canvas.height / 2 + 100,
            'highlight': this.canvas.height / 2 + 150,
            'names': this.canvas.height / 2 - 100
        };
        return positions[style] || this.canvas.height / 2;
    }

    getTextFont(style) {
        const fonts = {
            'title': 'bold 48px Arial',
            'subtitle': '32px Arial',
            'highlight': 'bold 36px Arial',
            'names': 'bold 40px Arial'
        };
        return fonts[style] || '24px Arial';
    }

    getTextColor(style) {
        const colors = {
            'title': '#FFFFFF',
            'subtitle': '#E0E0E0',
            'highlight': '#FFD700',
            'names': '#FFFFFF'
        };
        return colors[style] || '#FFFFFF';
    }

    /**
     * Get download URL for the canvas
     */
    getDownloadUrl() {
        return this.canvas.toDataURL('image/png');
    }

    /**
     * Download the shareable as image
     */
    download(filename = 'cosmic-shareable.png') {
        const link = document.createElement('a');
        link.download = filename;
        link.href = this.getDownloadUrl();
        link.click();
    }

    /**
     * Stop animation
     */
    stop() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
    }
}

// Global instance
window.CosmicShareables = CosmicShareables;

// Utility functions for easy integration
window.generateCosmicShareable = async function(containerId, birthDate) {
    const shareable = new CosmicShareables();
    const [year, month, day] = birthDate.split('-');
    
    return await shareable.generateShareable(containerId, 'cosmic', {
        month: parseInt(month),
        day: parseInt(day),
        year: parseInt(year)
    });
};

window.generateCompatibilityShareable = async function(containerId, data) {
    const shareable = new CosmicShareables();
    return await shareable.generateShareable(containerId, 'compatibility', data);
};

window.generateRarityShareable = async function(containerId, data) {
    const shareable = new CosmicShareables();
    return await shareable.generateShareable(containerId, 'rarity', data);
};