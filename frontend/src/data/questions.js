export const questions = [
  {
    id: 'duration',
    type: 'single',
    question: 'How long do you want to travel?',
    options: [
      { value: 'short', label: 'A short trip (2–4 days)' },
      { value: 'medium', label: 'A week or so (5–8 days)' },
      { value: 'long', label: 'A longer escape (9+ days)' },
    ],
  },
  {
    id: 'interests',
    type: 'multi',
    maxSelect: 4,
    question: 'What do you enjoy most while traveling? (pick up to 4)',
    options: [
      { value: 'beach', label: 'Beaches' },
      { value: 'nature', label: 'Mountains & nature' },
      { value: 'history', label: 'History & museums' },
      { value: 'food', label: 'Food' },
      { value: 'nightlife', label: 'Nightlife' },
      { value: 'adventure', label: 'Adventure' },
      { value: 'relaxation', label: 'Relaxation' },
      { value: 'family', label: 'Family-friendly activities' },
    ],
  },
  {
    id: 'weather',
    type: 'single',
    question: 'What weather do you prefer?',
    options: [
      { value: 'hot', label: 'Hot (28°C+)' },
      { value: 'warm', label: 'Warm (22–28°C)' },
      { value: 'mild', label: 'Mild (15–22°C)' },
      { value: 'cold', label: 'Cool / cold (below 15°C)' },
    ],
  },
  {
    id: 'companions',
    type: 'single',
    question: 'Who are you traveling with?',
    options: [
      { value: 'solo', label: 'Just me' },
      { value: 'partner', label: 'My partner' },
      { value: 'friends', label: 'Friends' },
      { value: 'family', label: 'Family (with kids)' },
    ],
  },
  {
    id: 'style',
    type: 'single',
    question: 'What is your travel style?',
    options: [
      { value: 'relaxed', label: 'Relaxed — slow mornings, no rush' },
      { value: 'balanced', label: 'Balanced — a mix of chill and activity' },
      { value: 'adventurous', label: 'Adventurous — pack the days full' },
    ],
  },
  {
    id: 'budgetLevel',
    type: 'single',
    question: 'What budget level fits you best?',
    options: [
      { value: 'budget', label: 'Budget-friendly' },
      { value: 'moderate', label: 'Moderate' },
      { value: 'luxury', label: 'Luxury' },
    ],
  },
  {
    id: 'budgetAmount',
    type: 'range',
    question: 'What is your total budget for the trip (per person, in EUR)?',
    min: 200,
    max: 3000,
    step: 50,
    default: 900,
  },
  {
    id: 'getaway',
    type: 'single',
    question: 'If you had to choose your perfect getaway, what would it be like?',
    options: [
      {
        value: 'modest_history',
        label: 'Modest and fair, rich with history — like Budapest',
      },
      {
        value: 'romantic_luxury',
        label: 'Romantic and iconic — like Paris',
      },
      {
        value: 'nature_adventure',
        label: 'Tropical, wild and full of adventure — like Bali',
      },
      {
        value: 'modern_luxury',
        label: 'Modern, glamorous and polished — like Dubai',
      },
      {
        value: 'food_nightlife',
        label: 'Buzzing with food and nightlife — like Bangkok',
      },
    ],
  },
  {
    id: 'season',
    type: 'single',
    question: 'Do you have a preferred season to travel in?',
    options: [
      { value: 'flexible', label: "I'm flexible" },
      { value: 'spring', label: 'Spring' },
      { value: 'summer', label: 'Summer' },
      { value: 'autumn', label: 'Autumn' },
      { value: 'winter', label: 'Winter' },
    ],
  },
]
