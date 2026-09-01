import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js'
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js'
import { z } from 'zod'

const BACKEND_URL = process.env.TRAVELMATCH_BACKEND_URL || 'http://localhost:8000/api'

const server = new McpServer({
  name: 'travelmatch',
  version: '1.0.0',
})

server.registerTool(
  'get_travel_recommendations',
  {
    title: 'Get travel recommendations',
    description:
      'Given a traveler\'s preferences, returns ranked destination matches with a match score, ' +
      'a personalized description, the best time to go, an estimated budget, and a recommended stay length.',
    inputSchema: {
      interests: z
        .array(z.enum(['beach', 'nature', 'history', 'food', 'nightlife', 'adventure', 'relaxation', 'family']))
        .describe('Up to 4 interest categories the traveler cares about'),
      weather: z.enum(['hot', 'warm', 'mild', 'cold']).describe('Preferred weather'),
      companions: z.enum(['solo', 'partner', 'friends', 'family']).describe('Who they are traveling with'),
      style: z.enum(['relaxed', 'balanced', 'adventurous']).describe('Travel style'),
      budgetLevel: z.enum(['budget', 'moderate', 'luxury']).describe('Overall budget level'),
      budgetAmount: z.number().describe('Total budget per person in EUR'),
      getaway: z
        .enum(['modest_history', 'romantic_luxury', 'nature_adventure', 'modern_luxury', 'food_nightlife'])
        .optional()
        .describe('The vibe of their ideal getaway'),
      duration: z.enum(['short', 'medium', 'long']).optional().describe('How long they want to travel for'),
      season: z.enum(['flexible', 'spring', 'summer', 'autumn', 'winter']).optional(),
    },
  },
  async (input) => {
    const response = await fetch(`${BACKEND_URL}/match`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(input),
    })

    if (!response.ok) {
      const body = await response.text()
      return {
        isError: true,
        content: [{ type: 'text', text: `TravelMatch backend returned ${response.status}: ${body}` }],
      }
    }

    const data = await response.json()

    return {
      content: [{ type: 'text', text: JSON.stringify(data.results, null, 2) }],
    }
  }
)

const transport = new StdioServerTransport()
await server.connect(transport)
