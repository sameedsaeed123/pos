export class Chart {
    constructor(ctx, config) {
      this.ctx = ctx
      this.config = config
    }
  
    destroy() {
      // Basic destroy method to prevent errors
      if (this.ctx) {
        this.ctx = null
      }
    }
  }
  