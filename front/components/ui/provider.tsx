"use client"

import {ChakraProvider, defaultConfig, createSystem, defineConfig, defineRecipe} from "@chakra-ui/react"
import {
  ColorModeProvider,
  type ColorModeProviderProps,
} from "./color-mode"

const buttonRecipe =  defineRecipe({
  variants: {
    variant: {
      solid: {
        colorPalette: "orange"
      }
    }
  }
});

const config = defineConfig({
  theme: {
    tokens: {
      color: {}
    },
    recipes: {
      button: buttonRecipe
    }
  }
});

const system = createSystem(defaultConfig, config);

export function Provider(props: ColorModeProviderProps) {
  return (
    <ChakraProvider value={system}>
      <ColorModeProvider {...props} />
    </ChakraProvider>
  )
}
