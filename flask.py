from flask import Flask, request, jsonify
import requests
import os

app = Flask(__name__)

CLARIFAI_API_KEY = "your_clarifai_api_key"
SPOONACULAR_API_KEY = "your_spoonacular_api_key"

@app.route('/food-recipes', methods=['POST'])
def get_recipes():
    image_url = request.json.get("image_url")
    if not image_url:
        return jsonify({"error": "image_url is required"}), 400

    # Clarifai API call
    clarifai_resp = requests.post(
        "https://api.clarifai.com/v2/models/general-image-recognition/outputs",
        headers={
            "Authorization": f"Key {CLARIFAI_API_KEY}",
            "Content-Type": "application/json"
        },
        json={"inputs": [{"data": {"image": {"url": image_url}}}]}
    ).json()

    try:
        food_name = clarifai_resp['outputs'][0]['data']['concepts'][0]['name']
    except:
        return jsonify({"error": "Failed to recognize image"}), 500

    # Spoonacular API call
    recipe_resp = requests.get(
        "https://api.spoonacular.com/recipes/complexSearch",
        params={"query": food_name, "number": 5, "apiKey": SPOONACULAR_API_KEY}
    ).json()

    return jsonify({
        "recognized_food": food_name,
        "recipes": recipe_resp.get("results", [])
    })

if __name__ == '__main__':
    app.run(debug=True)
