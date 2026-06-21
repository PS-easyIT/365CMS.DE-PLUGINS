export interface FontSizes {
  base: number;
  hero: number;
  sectionTitle: number;
  cardTitle: number;
}

export interface GlobalDesignConfig {
  fontSizes: FontSizes;
  headerSpacing: number;
  footerSpacing: number;
  cardSpacing: number;
  sectionContentSpacing: number;
}

export interface LandingpageDesignOverrides {
  fontSizes?: Partial<FontSizes>;
  headerSpacing?: number;
  footerSpacing?: number;
  cardSpacing?: number;
  sectionContentSpacing?: number;
}

export interface LandingpageSettings {
  slug: string;
  title: string;
  useGlobalDesign: boolean;
  design?: LandingpageDesignOverrides;
}

export const DEFAULT_GLOBAL_DESIGN: GlobalDesignConfig = {
  fontSizes: {
    base: 16,
    hero: 52,
    sectionTitle: 32,
    cardTitle: 21,
  },
  headerSpacing: 48,
  footerSpacing: 48,
  cardSpacing: 24,
  sectionContentSpacing: 24,
};

const clamp = (value: unknown, fallback: number, min: number, max: number): number => {
  const parsed = typeof value === 'number' && Number.isFinite(value) ? value : fallback;
  return Math.max(min, Math.min(max, Math.round(parsed)));
};

export const normalizeGlobalDesign = (config: Partial<GlobalDesignConfig> = {}): GlobalDesignConfig => ({
  fontSizes: {
    base: clamp(config.fontSizes?.base, DEFAULT_GLOBAL_DESIGN.fontSizes.base, 12, 24),
    hero: clamp(config.fontSizes?.hero, DEFAULT_GLOBAL_DESIGN.fontSizes.hero, 28, 96),
    sectionTitle: clamp(config.fontSizes?.sectionTitle, DEFAULT_GLOBAL_DESIGN.fontSizes.sectionTitle, 20, 72),
    cardTitle: clamp(config.fontSizes?.cardTitle, DEFAULT_GLOBAL_DESIGN.fontSizes.cardTitle, 16, 40),
  },
  headerSpacing: clamp(config.headerSpacing, DEFAULT_GLOBAL_DESIGN.headerSpacing, 0, 160),
  footerSpacing: clamp(config.footerSpacing, DEFAULT_GLOBAL_DESIGN.footerSpacing, 0, 160),
  cardSpacing: clamp(config.cardSpacing, DEFAULT_GLOBAL_DESIGN.cardSpacing, 0, 96),
  sectionContentSpacing: clamp(config.sectionContentSpacing, DEFAULT_GLOBAL_DESIGN.sectionContentSpacing, 0, 160),
});

export const resolveLandingpageDesign = (
  globalConfig: Partial<GlobalDesignConfig>,
  pageSettings: LandingpageSettings,
): GlobalDesignConfig => {
  const normalizedGlobal = normalizeGlobalDesign(globalConfig);

  if (pageSettings.useGlobalDesign) {
    return normalizedGlobal;
  }

  return normalizeGlobalDesign({
    ...DEFAULT_GLOBAL_DESIGN,
    ...pageSettings.design,
    fontSizes: {
      ...DEFAULT_GLOBAL_DESIGN.fontSizes,
      ...pageSettings.design?.fontSizes,
    },
  });
};

export const toCssVariables = (design: GlobalDesignConfig): Record<string, string> => ({
  '--beratung-font-base': `${design.fontSizes.base}px`,
  '--beratung-font-hero': `${design.fontSizes.hero}px`,
  '--beratung-font-section-title': `${design.fontSizes.sectionTitle}px`,
  '--beratung-font-card-title': `${design.fontSizes.cardTitle}px`,
  '--beratung-header-spacing': `${design.headerSpacing}px`,
  '--beratung-footer-spacing': `${design.footerSpacing}px`,
  '--beratung-card-spacing': `${design.cardSpacing}px`,
  '--beratung-section-content-spacing': `${design.sectionContentSpacing}px`,
});
