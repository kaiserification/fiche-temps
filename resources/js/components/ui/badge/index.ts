import { cva, type VariantProps } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva('inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium', {
    variants: {
        variant: {
            default: 'bg-primary/10 text-primary',
            success: 'bg-success-muted text-success',
            warning: 'bg-warning-muted text-warning',
            destructive: 'bg-destructive-muted text-destructive',
            info: 'bg-info-muted text-info',
            muted: 'bg-muted text-muted-foreground',
            outline: 'border border-border text-foreground',
        },
    },
    defaultVariants: {
        variant: 'default',
    },
});

export type BadgeVariants = VariantProps<typeof badgeVariants>;
